# Example: JIRA Comment

```
h3. Code review completed — 2 findings

h4. Findings
* {{PROJ-1234}} — *Moderate*: Shipping-fee logic is duplicated in {{Order}} model and {{ShippingService}}. Recommend reusing the service method.
* {{PROJ-1234}} — *Minor*: Typo in validation message ("Oder" → "Order").

h4. Testing recommendations
* Verify order creation with standard shipping: https://app.example.com/orders/new?type=standard
* Verify order creation with express shipping: https://app.example.com/orders/new?type=express
* Check invoice PDF reflects correct shipping fee: https://app.example.com/orders/42/invoice
```
