# Hoja de Ruta de Mejoras Técnicas: Dead Simple Inventory Manager

Este documento detalla la estrategia técnica para evolucionar el sistema hacia una solución robusta y profesional. Las propuestas están ordenadas por su **facilidad de implementación**, permitiendo una mejora incremental del sistema.

---

## 1. Rendimiento: SQL Scopes para "Bajo Stock"
**Dificultad:** Muy Baja | **Prioridad:** Media | **Impacto:** Alto en escalabilidad.

### El Problema: Filtrado en Memoria (PHP)
Actualmente, la lógica para identificar productos en estado de advertencia reside en un atributo calculado (`warning`) en el modelo `Product`. Esto obliga al servidor a realizar un `SELECT * FROM products`, instanciar miles de objetos en RAM y luego filtrarlos en PHP. Este enfoque colapsa con catálogos grandes.

### La Solución: Filtrado en el Motor de Base de Datos
Implementar un **Local Scope** en el modelo `Product` que traduzca esta lógica a una cláusula `WHERE` de SQL nativa.

**Ubicación:** `app/Models/Product.php`

```php
use Illuminate\Database\Eloquent\Builder;

/**
 * Filtra productos cuyo stock es menor o igual al límite de advertencia.
 */
public function scopeLowStock(Builder $query): void {
    // Genera SQL: WHERE products.stock <= products.min_stock_warning
    $query->whereColumn('stock', '<=', 'min_stock_warning');
}
```

### Detalle Técnico y SQL Resultante
Al invocar `Product::lowStock()->get()`, el motor de base de datos descarta los registros innecesarios antes de enviarlos a PHP.
- **SQL Generado:** `SELECT * FROM products WHERE stock <= min_stock_warning;`
- **Paginación:** Permite el uso de `Product::lowStock()->paginate(20)`, lo cual es imposible con atributos calculados en memoria.

---

## 2. Refactorización: Desacoplamiento con Observers
**Dificultad:** Baja | **Prioridad:** Baja | **Impacto:** Alto en mantenibilidad.

### El Problema: Modelos "Gordos" (Fat Models)
El modelo `Product` utiliza Mutators (`Attribute::set`) para actualizar los campos `last_stock_update` y `last_price_update`. Esto mezcla la definición de la entidad con la lógica de auditoría, dificultando la escalabilidad y violando el principio de Responsabilidad Única.

### La Solución: Observador de Modelo
Extraer la lógica de auditoría a una clase dedicada que escuche los eventos del ciclo de vida de Eloquent.

**1. Crear el Observador:** `app/Observers/ProductObserver.php`
```php
namespace App\Observers;
use App\Models\Product;

class ProductObserver {
    public function updating(Product $product): void {
        // isDirty() verifica cambios reales antes de persistir en DB
        if ($product->isDirty('price')) {
            $product->last_price_update = now();
        }
        if ($product->isDirty('stock')) {
            $product->last_stock_update = now();
        }
    }
}
```

**2. Registro en Laravel 12:** `app/Providers/AppServiceProvider.php`
```php
public function boot(): void {
    \App\Models\Product::observe(\App\Observers\ProductObserver::class);
}
```

---

## 3. Historial de Movimientos (Kardex / Audit Log)
**Dificultad:** Media | **Prioridad:** Alta | **Impacto:** Crítico para integridad.

### El Problema: Inmutabilidad y Trazabilidad
El sistema actual sobrescribe el stock, borrando el estado anterior. Sin un log inmutable, es imposible realizar auditorías sobre pérdidas, robos o errores de entrada de mercancía.

### La Solución: Registro de Movimientos Transaccional
Crear una tabla de movimientos y un servicio que garantice que el stock nunca cambie sin un registro asociado.

#### A. Esquema de Base de Datos
```php
Schema::create('inventory_movements', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignIdFor(Product::class)->constrained();
    $table->foreignIdFor(User::class)->constrained();
    $table->enum('type', ['purchase', 'sale', 'adjustment', 'return']);
    $table->integer('quantity'); // El cambio aplicado (positivo o negativo)
    $table->integer('previous_stock'); // Snapshot del stock antes del cambio
    $table->integer('new_stock'); // Snapshot del stock después del cambio
    $table->text('notes')->nullable();
    $table->timestamps();
    $table->index(['product_id', 'created_at']);
});
```

#### B. Capa de Negocio (`InventoryService`)
El servicio encapsula la lógica para asegurar transaccionalidad ACID.
```php
public function adjustStock(Product $product, int $diff, string $type, ?string $notes = null) {
    return DB::transaction(function () use ($product, $diff, $type, $notes) {
        // 1. Crear log inmutable
        InventoryMovement::create([
            'product_id' => $product->id,
            'user_id' => auth()->id(),
            'type' => $type,
            'quantity' => $diff,
            'previous_stock' => $product->stock,
            'new_stock' => $product->stock + $diff,
            'notes' => $notes,
        ]);
        // 2. Actualizar stock actual
        return $product->increment('stock', $diff);
    });
}
```

---

## 4. Operaciones Masivas y Trazabilidad Integrada
**Dificultad:** Alta | **Prioridad:** Alta | **Impacto:** Máximo en eficiencia operativa.

### El Problema: Procesamiento Ineficiente
Actualizar masivamente precios o stock mediante bucles tradicionales en controladores genera múltiples peticiones HTTP innecesarias o desbordamientos de memoria en el servidor.

### La Solución: Orquestador de Lotes con Chunking
Implementar un controlador `BulkOperationController` que utilice procesamiento por trozos para manejar grandes volúmenes de datos.

#### A. Actualización Masiva de Precios
Permite filtrar por marca, categoría o proveedor y aplicar reglas (Porcentaje o Monto Fijo).
- **Técnica:** Uso de `chunkById(100)` para procesar de 100 en 100 productos, manteniendo el consumo de RAM constante.
- **Flujo:** La actualización de cada producto disparará el `ProductObserver` (Propuesta #2), manteniendo los timestamps de auditoría al día automáticamente.

#### B. Log de Actividad Global
A diferencia del Kardex (que es micro), el sistema creará un `ActivityLog` (macro) para documentar la operación masiva completa.
```php
ActivityLog::create([
    'user_id' => auth()->id(),
    'action' => 'bulk_price_update',
    'description' => "Aumento del 15% en categoría Electrónica",
    'payload' => json_encode($request_parameters) 
]);
```

### Resumen de Implementación
1. **Validación:** Validar que el usuario tenga permisos sobre todos los productos seleccionados.
2. **Transacción:** Envolver toda la operación en un `DB::transaction`.
3. **Ejecución:** Iterar mediante trozos y aplicar la lógica matemática de ajuste.
4. **Respuesta:** Devolver un resumen con la cantidad de productos afectados.
