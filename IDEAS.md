# 📔 Manual de Refactorización y Estándares Técnicos

Este documento detalla las deficiencias técnicas detectadas en el proyecto **Dead Simple Inventory Manager** y prescribe soluciones exactas para elevar su mantenibilidad, seguridad y escalabilidad siguiendo los estándares de Laravel 12 y PHP 8.4.

---

## 1. Acciones de Única Responsabilidad (Action Classes)

### **El Problema**
La lógica de negocio reside directamente en los controladores o en servicios "bolsa" (God Services).
- **Ejemplo:** `ProductController@store` gestiona el stock inicial. `ProductController@updateImage` gestiona el disco y la manipulación de imágenes.
- **Riesgos:** Controladores "gordos", lógica difícil de reutilizar en comandos CLI o tareas programadas, y dificultad para realizar pruebas unitarias aisladas.

### **La Solución**
Implementar el **Patrón Action**. Clases con un único método público `execute()` que encapsulan una operación lógica completa.

### **La Receta**
1. **Crear Directorio:** `app/Actions/`.
2. **Estructura de la Acción:**
   ```php
   namespace App\Actions\Inventory;

   class AdjustStockAction {
       public function execute(User $user, Product $product, int $quantity, MovementType $type, ?string $notes = null): Product {
           return \DB::transaction(fn() => /* Lógica */);
       }
   }
   ```
3. **Inyección en Controlador:**
   ```php
   public function store(StoreRequest $request, AdjustStockAction $adjustStock) {
       $product = ...;
       $adjustStock->execute(auth()->user(), $product, ...);
   }
   ```

---

## 2. Capa de Transformación (API Resources)

### **El Problema**
Los controladores devuelven modelos Eloquent directamente.
- **Riesgos:** Filtración accidental de campos internos (`id` vs `uuid`, `password_hash`). El cambio de nombre de una columna en la base de datos rompe inmediatamente la API. Inconsistencia en formatos de fecha o precios.

### **La Solución**
Usar **Laravel JsonResources**. Actúan como una capa de presentación que protege el contrato de la API.

### **La Receta**
1. **Generar:** `php artisan make:resource Product/ProductResource`.
2. **Mapear explícitamente:**
   ```php
   public function toArray(Request $request): array {
       return [
           'uuid' => $this->uuid,
           'name' => $this->name,
           'sku' => $this->code,
           'stock' => [
               'count' => $this->stock,
               'is_low' => $this->stock <= $this->min_stock_warning,
           ],
           'brand' => new BrandResource($this->whenLoaded('brand')),
       ];
   }
   ```

---

## 3. Patrón Strategy para Operaciones Masivas

### **El Problema**
`BulkOperationController` utiliza condicionales `if ($type === 'price_percentage')`.
- **Riesgos:** Violación del principio Open/Closed. Agregar un nuevo tipo de operación requiere modificar el controlador y el servicio, aumentando la complejidad ciclomática.

### **La Solución**
Implementar el **Patrón Strategy**. Cada tipo de ajuste es una clase independiente que implementa una interfaz común.

### **La Receta**
1. **Interfaz:** `app/Contracts/PriceAdjustmentStrategy.php` con el método `apply()`.
2. **Implementaciones:** `PercentageAdjustmentStrategy`, `FixedAdjustmentStrategy`.
3. **Factory:** Una clase que resuelva la estrategia correcta basada en el valor del Enum recibido en la petición.

---

## 4. Estándares de Calidad y DX (Developer Experience)

### **El Problema**
Presencia de consultas N+1 indetectadas, asignación masiva de atributos no deseados y falta de rigor en tipos de retorno.

### **La Solución**
Activar el modo estricto de Eloquent y forzar tipos de PHP 8.4.

### **La Receta**
1. **Model Strictness (`AppServiceProvider.php`):**
   ```php
   public function boot(): void {
       \Illuminate\Database\Eloquent\Model::shouldBeStrict(! $this->app->isProduction());
   }
   ```
   *Esto lanzará excepciones en desarrollo por Lazy Loading, atributos faltantes o descartados.*
2. **Strict Types:** Todos los archivos PHP deben comenzar con `declare(strict_types=1);`.
3. **Traducciones:** Mover todos los mensajes de éxito/error de los controladores a `lang/en/responses.php`.

---