# Hoja de Ruta de Mejoras Técnicas: Dead Simple Inventory Manager

Este documento detalla la estrategia técnica para evolucionar el sistema hacia una solución robusta y profesional. Las propuestas están ordenadas por su **facilidad de implementación**, permitiendo una mejora incremental del sistema. La implementación prioriza paquetes convencionales como `spatie/laravel-activitylog` para logging global, permitiendo evolución incremental desde trazabilidad básica hacia auditoría completa.

---

## 1. Trazabilidad Integrada
**Dificultad:** Alta | **Prioridad:** Alta | **Impacto:** Trazabilidad

#### A. Log de Actividad Macro (Operaciones Masivas) - LEGACY
A diferencia del Kardex (que es micro), el sistema creará un `ActivityLog` (macro) para documentar la operación masiva completa.
```php
ActivityLog::create([
    'user_id' => auth()->id(),
    'action' => 'bulk_price_update',
    'description' => "Aumento del 15% en categoría Electrónica",
    'payload' => json_encode($request_parameters) 
]);
```

#### B. Sistema Global de ActivityLog
**Dificultad:** Media | **Prioridad:** Alta | **Impacto:** Trazabilidad Completa

Para una auditoría completa (no solo masivas), implementa `spatie/laravel-activitylog`, un paquete Laravel convencional que loggea automáticamente todas las acciones de usuarios en una tabla `activity_log`. Complementa el Kardex (InventoryMovement para cambios de stock) sin duplicación.

##### Instalación y Configuración
- Instala el paquete: Ejecuta `composer require spatie/laravel-activitylog` en la terminal. Esto agrega el paquete a `composer.json` y registra su service provider automáticamente en Laravel.
- Publica archivos: 
  - `php artisan vendor:publish --provider="Spatie\Activitylog\ActivitylogServiceProvider" --tag="activitylog-migrations"` para obtener la migración de la tabla `activity_log` (campos: id, log_name, description, subject_type/id, causer_type/id, properties [JSON con cambios], created_at/updated_at).
  - Opcional: `php artisan vendor:publish --provider="Spatie\Activitylog\ActivitylogServiceProvider" --tag="activitylog-config"` para personalizar `config/activitylog.php` (e.g., habilitar/deshabilitar logging global, setear retención de logs a 365 días).
- Migra: `php artisan migrate` crea la tabla. En `.env`, agrega `ACTIVITY_LOGGER_ENABLED=true` para activar.

##### Configuración de Modelos
- Agrega `use Spatie\Activitylog\Traits\CausesActivity;` al modelo `User` (app/Models/User.php). Esto permite rastrear actividades causadas por usuarios (e.g., `$user->actions` para consultar logs).
- Para modelos a auditar (e.g., Product, Brand, Category), agrega `use Spatie\Activitylog\Traits\LogsActivity;` y el método `getActivitylogOptions()`:
  ```php
  use Spatie\Activitylog\LogOptions;
  
  public function getActivitylogOptions(): LogOptions
  {
      return LogOptions::defaults()
          ->logOnly(['name', 'price', 'code'])  // Solo campos clave (excluye 'stock' para evitar duplicación con Kardex)
          ->logOnlyDirty()  // Loggea solo cambios reales (no saves sin modificaciones)
          ->dontSubmitEmptyLogs()  // Evita entradas vacías
          ->setDescriptionForEvent(fn(string $eventName) => "Producto {$eventName} por {causer.name}");  // Descripción dinámica con placeholders (e.g., "Producto updated por Juan")
  }
  ```
  - Técnicamente: `logOnlyDirty()` compara old/new valores; `dontSubmitEmptyLogs()` previene inserts innecesarios; placeholders como `{causer.name}` se resuelven automáticamente.

##### Integración y Uso
- **Logging Automático:** Al guardar modelos (e.g., en ProductController::update), el paquete loggea automáticamente via eventos (created/updated/deleted).
- **Operaciones Masivas:** En BulkOperationController, envuelve en `LogBatch::startBatch(); ... LogBatch::endBatch();` para agrupar logs en un batch (reduce entradas individuales, mejora performance).
- **Acciones No-Modelo:** Para logins/logout, usa `activity()->log('Usuario inició sesión')` en controladores.
- **Consultas:** Usa `Activity::latest()->paginate()` para dashboards; filtra por `$user->actions`.
- **Limpieza:** Programa `php artisan activitylog:clean` para borrar logs viejos.

##### Trade-offs Técnicos
- **Performance:** Agrega writes a DB por cada cambio; mitiga con `logOnlyDirty()` (solo loggea si hay diferencias) y deshabilita en imports (`activity()->disableLogging()`).
- **Almacenamiento:** Tabla crece rápido; configura retención para evitar bloat.
- **Seguridad:** No loggea datos sensibles (e.g., passwords); usa `dontLogIfAttributesChangedOnly(['password'])`.
- **Beneficios:** Queryable (busca por usuario/fecha), JSON properties para diffs, integración con Laravel (auth auto-detecta causer).

## 2. Operaciones Masivas
**Dificultad:** Alta | **Prioridad:** Alta | **Impacto:** Máximo en eficiencia operativa.

### El Problema: Procesamiento Ineficiente
Actualizar masivamente precios o stock mediante bucles tradicionales en controladores genera múltiples peticiones HTTP innecesarias o desbordamientos de memoria en el servidor.

### La Solución: Orquestador de Lotes con Chunking
Implementar un controlador `BulkOperationController` que utilice procesamiento por trozos para manejar grandes volúmenes de datos.

#### A. Actualización Masiva de Precios
Permite filtrar por marca, categoría o proveedor y aplicar reglas (Porcentaje o Monto Fijo).
- **Técnica:** Uso de `chunkById(100)` para procesar de 100 en 100 productos, manteniendo el consumo de RAM constante.
- **Flujo:** La actualización de cada producto disparará el `ProductObserver` (Propuesta #2), manteniendo los timestamps de auditoría al día automáticamente. Además, integra con ActivityLog global: envuelve la actualización en `LogBatch` para logging por lotes (ver Sección 1.B).
