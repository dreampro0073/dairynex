# Mobile API v1

All mobile endpoints are JSON endpoints beneath `/api/v1`. They use a Sanctum bearer token and share the same services and tenant checks as the Inertia web application.

Obtain a token with `POST /api/v1/auth/token`:

```json
{"login":"email-or-mobile","client_id":1,"password":"…","device_name":"ios-app"}
```

Pass the returned token as `Authorization: Bearer <token>`. `DELETE /api/v1/auth/token` revokes the current token. Tokens are issued only to active accounts that do not need a temporary-password change.

| Endpoint | Roles |
| --- | --- |
| `GET /me` | All active roles |
| `GET /deliveries`, `GET /deliveries/{id}` | Client Admin, Delivery Boy, Customer; results are tenant- and ownership-scoped |
| `POST /deliveries/{id}/complete` | Assigned Delivery Boy |
| `POST /deliveries/{id}/pin` | Owning Customer |
| `POST /payments` | Client Admin, or Delivery Boy collecting for today's assigned customer |
| `GET /subscriptions` | Client Admin or owning Customer |
| `POST /subscriptions` | Client Admin |
| `POST /subscriptions/{id}/pause` | Client Admin or owning Customer |

The delivery PIN response has `Cache-Control: no-store`; PIN verification, payment idempotency, credit checks, subscription scheduling, and tenant boundaries remain in backend services rather than API controllers.
