# Plan: Compra de Días Fijos de Acceso (Pago Único acumulativo)

## Fase 1: Pasarela de Pago Único (Mercado Pago)
- [ ] Tarea: Reemplazar el método `createPreapproval` por `createPaymentPreference` in `MercadoPagoService` apuntando a `/checkout/preferences`.
- [ ] Tarea: Actualizar `SubscriptionCheckoutController` para instanciar la preferencia de pago único y guardar el ID de la preferencia en `provider_subscription_id`.
- [ ] Tarea: Actualizar los tests de checkout para simular la API de preferencias de Mercado Pago y validar que la suscripción inicial quede en estado `pending`.
- [ ] Task: Conductor - User Manual Verification 'Fase 1: Pasarela de Pago Único (Mercado Pago)' (Protocol in workflow.md)

## Fase 2: Acumulación de Días y Webhook
- [ ] Tarea: Actualizar la lógica del webhook de Mercado Pago en `api.php` para procesar notificaciones de pago (`payment`) y realizar la acumulación de días (`ends_at` extendido desde el `ends_at` anterior del usuario si aún está activo).
- [ ] Tarea: Modificar el endpoint `GET /api/user/subscription` para retornar `days_remaining` indicando los días enteros que quedan antes de expirar.
- [ ] Tarea: Escribir tests de integración para simular múltiples webhooks seguidos y validar que la fecha de vencimiento (`ends_at`) sume correctamente los días correspondientes (ej. 30 días + 90 días).
- [ ] Task: Conductor - User Manual Verification 'Fase 2: Acumulación de Días y Webhook' (Protocol in workflow.md)
