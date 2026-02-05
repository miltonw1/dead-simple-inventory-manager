# Hoja de Ruta de Mejoras Técnicas: Dead Simple Inventory Manager

Este documento detalla la estrategia técnica para evolucionar el sistema hacia una solución robusta y profesional. Las propuestas están ordenadas por su **facilidad de implementación**, permitiendo una mejora incremental del sistema. La implementación prioriza paquetes convencionales como `spatie/laravel-activitylog` para logging global, permitiendo evolución incremental desde trazabilidad básica hacia auditoría completa.

---

## 1. Operaciones Masivas
**Dificultad:** Alta | **Prioridad:** Alta | **Impacto:** Máximo en eficiencia operativa.

### El Problema: Procesamiento Ineficiente
Actualizar masivamente precios o stock mediante bucles tradicionales en controladores genera múltiples peticiones HTTP innecesarias o desbordamientos de memoria en el servidor.

### La Solución: Orquestador de Lotes con Chunking
Implementar un controlador `BulkOperationController` que utilice procesamiento por trozos para manejar grandes volúmenes de datos.

#### A. Actualización Masiva de Precios
Permite filtrar por marca, categoría o proveedor y aplicar reglas (Porcentaje o Monto Fijo).
- **Técnica:** Uso de `chunkById(100)` para procesar de 100 en 100 productos, manteniendo el consumo de RAM constante.
- **Flujo:** La actualización de cada producto disparará el `ProductObserver` (Propuesta #2), manteniendo los timestamps de auditoría al día automáticamente. Además, se integra con el sistema de ActivityLog global (usando `LogBatch` para agrupar logs).
