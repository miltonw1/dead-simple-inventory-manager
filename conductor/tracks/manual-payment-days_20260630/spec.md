# Track: Compra de Días Fijos de Acceso (Pago Único acumulativo)

## 1. Descripción
Este track reemplaza el flujo de suscripción recurrente automática (preapproval) por un modelo de compra manual de días fijos de acceso (Pago Único). Los usuarios podrán adquirir pases de 30 días (mensual), 90 días (trimestral) o 365 días (anual) mediante Preferencias de Pago Único de Mercado Pago. Si el usuario adquiere un plan mientras posee tiempo de acceso vigente, los nuevos días se acumularán a partir de su fecha límite de expiración actual (`ends_at`).

## 2. Requerimientos Funcionales
1. **Generación de Preferencias de Pago Único**:
   - Modificar `MercadoPagoService` para que use el endpoint `/checkout/preferences` en lugar de `/preapproval`.
   - Modificar `SubscriptionCheckoutController` para solicitar un link de preferencia de pago único de Mercado Pago.
2. **Acumulación de Acceso (Webhook)**:
   - Cuando se recibe el webhook de pago aprobado, si el usuario ya cuenta con un acceso activo (`ends_at` en el futuro), la nueva fecha `ends_at` se calculará sumando la cantidad de días del plan (30, 90 o 365) a la fecha `ends_at` actual.
   - Si el acceso ya expiró o el usuario no posee registros activos, se activará el acceso calculando las fechas desde `now()`.
3. **API e Información al Frontend**:
   - En `GET /api/user/subscription`, incluir un campo calculado de `days_remaining` indicando la cantidad de días restantes que tiene el usuario, permitiendo facilitar la transición de la UI hacia un lenguaje de "días restantes".

## 3. Criterios de Aceptación
- La creación del checkout devuelve una URL válida de Mercado Pago única (`preferences`).
- Un webhook de pago exitoso acumula correctamente los días sobre una suscripción existente (`ends_at = ends_at + X días`).
- Si el usuario no tiene acceso vigente, el pago exitoso inicializa el período desde el momento actual.
- Se cuenta con tests funcionales que verifiquen el cálculo correcto de la fecha límite ante compras múltiples y consecutivas.
