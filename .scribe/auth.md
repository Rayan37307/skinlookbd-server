# Authenticating requests

To authenticate requests, include an **`Authorization`** header with the value **`"Bearer {YOUR_SANCTUM_TOKEN}"`**.

All authenticated endpoints are marked with a `requires authentication` badge in the documentation below.

Obtain a token via <code>POST /api/v1/auth/login</code> or <code>/register</code>, then send it as <code>Authorization: Bearer {token}</code>.
