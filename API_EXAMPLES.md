# 📋 API EXAMPLES - COMPLETE ORDER WORKFLOW

This file contains ready-to-use API request examples for testing the complete workflow.

## 🔐 Authentication Headers
```
Authorization: Bearer {token}
Content-Type: application/json
```

---

## 👥 ROLE-BASED ENDPOINTS

### CUSTOMER ENDPOINTS

#### 1️⃣ Place Order (Auto-Confirms)
```http
POST /api/customer/orders
Content-Type: application/json
Authorization: Bearer {customer_token}

{
  "company_id": 1,
  "branch_id": 1,
  "delivery_address_id": 5,
  "payment_method": "card",
  "notes": "Please ring doorbell twice",
  "items": [
    {
      "menu_item_id": 10,
      "quantity": 2,
      "options": [1, 2, 3]
    },
    {
      "menu_item_id": 11,
      "quantity": 1,
      "options": []
    }
  ]
}

Response (201):
{
  "success": true,
  "data": {
    "id": 1,
    "order_number": "ORD-20260422-ABC123",
    "status": "confirmed",
    "customer": {
      "name": "John Doe",
      "phone": "+1234567890"
    },
    "items": [
      {
        "name": "Burger",
        "price": 15.00,
        "quantity": 2,
        "options": ["Extra Cheese", "No Onions"]
      }
    ],
    "invoice": {
      "subtotal": 30.00,
      "tax": 3.00,
      "delivery_fee": 5.00,
      "total": 38.00
    },
    "estimated_time": "25 minutes",
    "created_at": "2026-04-22T10:30:00Z"
  },
  "message": "Order placed and confirmed successfully"
}
```

#### 2️⃣ Update Pending Order
```http
PUT /api/customer/orders/1
Content-Type: application/json
Authorization: Bearer {customer_token}

{
  "delivery_address_id": 6,
  "notes": "Updated notes",
  "items": [
    {
      "menu_item_id": 10,
      "quantity": 3,
      "options": [1, 2]
    }
  ]
}

Response (200):
{
  "success": true,
  "data": { ... },
  "message": "Order updated successfully"
}
```

#### 3️⃣ Cancel Order
```http
DELETE /api/customer/orders/1
Content-Type: application/json
Authorization: Bearer {customer_token}

{
  "reason": "Changed my mind"
}

Response (200):
{
  "success": true,
  "data": {
    "id": 1,
    "status": "cancelled",
    "payment": {
      "payment_status": "refunded",
      "amount": 38.00
    }
  },
  "message": "Order cancelled successfully. Refund will be processed"
}
```

#### 4️⃣ Get My Orders
```http
GET /api/customer/orders
Authorization: Bearer {customer_token}

Response (200):
{
  "success": true,
  "data": [
    {
      "id": 1,
      "order_number": "ORD-20260422-ABC123",
      "status": "delivered",
      "branch": { ... },
      "items": [ ... ],
      "created_at": "2026-04-22T10:30:00Z"
    }
  ]
}
```

#### 5️⃣ Get Active Orders (In-Progress)
```http
GET /api/customer/orders/active/list
Authorization: Bearer {customer_token}

Response (200):
{
  "success": true,
  "data": [
    {
      "id": 2,
      "order_number": "ORD-20260422-DEF456",
      "status": "picked_up",
      "branch": { ... },
      "delivery": {
        "delivery_status": "picked_up",
        "driver": {
          "name": "Ahmed",
          "phone": "+1987654321"
        }
      }
    }
  ]
}
```

#### 6️⃣ Get Order Details
```http
GET /api/customer/orders/1
Authorization: Bearer {customer_token}

Response (200):
{
  "success": true,
  "data": {
    "id": 1,
    "order_number": "ORD-20260422-ABC123",
    "status": "picked_up",
    "customer": { ... },
    "branch": { ... },
    "items": [
      {
        "name": "Burger",
        "price": 15.00,
        "quantity": 2,
        "options": ["Extra Cheese"]
      }
    ],
    "delivery": {
      "delivery_status": "picked_up",
      "driver": {
        "name": "Ahmed",
        "phone": "+1987654321"
      },
      "picked_up_at": "2026-04-22T10:55:00Z"
    },
    "payment": {
      "payment_method": "card",
      "payment_status": "pending"
    }
  }
}
```

#### 7️⃣ Track Delivery (Live Tracking)
```http
GET /api/customer/orders/1/track
Authorization: Bearer {customer_token}

Response (200):
{
  "success": true,
  "data": {
    "order_number": "ORD-20260422-ABC123",
    "status": "picked_up",
    "delivery_status": "picked_up",
    "driver": {
      "name": "Ahmed",
      "phone": "+1987654321",
      "vehicle": "Honda Civic - White"
    },
    "estimated_arrival": "2026-04-22T11:15:00Z"
  }
}
```

---

### EMPLOYEE ENDPOINTS

#### 1️⃣ Get Kitchen Orders (In-Progress)
```http
GET /api/employee/orders
Authorization: Bearer {employee_token}

Response (200):
{
  "success": true,
  "data": [
    {
      "id": 1,
      "order_number": "ORD-20260422-ABC123",
      "status": "confirmed",
      "customer": {
        "name": "John Doe",
        "phone": "+1234567890"
      },
      "items": [
        {
          "name": "Burger",
          "quantity": 2,
          "options": ["Extra Cheese"]
        }
      ]
    }
  ]
}
```

#### 2️⃣ Get Order Details
```http
GET /api/employee/orders/1
Authorization: Bearer {employee_token}

Response (200):
{
  "success": true,
  "data": {
    "id": 1,
    "order_number": "ORD-20260422-ABC123",
    "status": "confirmed",
    "customer": { ... },
    "items": [
      {
        "item_name_snapshot": "Burger",
        "quantity": 2,
        "options": [
          {
            "option_name_snapshot": "Extra Cheese",
            "extra_price": 0.50
          }
        ]
      }
    ]
  }
}
```

#### 3️⃣ Mark Order as PREPARING
```http
PATCH /api/employee/orders/1/mark-preparing
Content-Type: application/json
Authorization: Bearer {employee_token}

{
  "notes": "Started cooking"
}

Response (200):
{
  "success": true,
  "data": {
    "id": 1,
    "status": "preparing",
    "order_number": "ORD-20260422-ABC123"
  },
  "message": "Order marked as preparing"
}

Behind scenes:
- Order status: CONFIRMED → PREPARING
- Event: OrderPreparing fired
- Customer notified: "Your order is being prepared"
```

#### 4️⃣ Mark Order as READY_FOR_PICKUP (Triggers Driver Assignment!)
```http
PATCH /api/employee/orders/1/mark-ready
Content-Type: application/json
Authorization: Bearer {employee_token}

{
  "notes": "Ready to go"
}

Response (200):
{
  "success": true,
  "data": {
    "id": 1,
    "status": "ready_for_pickup",
    "delivery": {
      "id": 100,
      "delivery_status": "unassigned"
    }
  },
  "message": "Order marked as ready. Driver assignment in progress..."
}

Behind scenes:
- Order status: PREPARING → READY_FOR_PICKUP
- Delivery created: UNASSIGNED
- AssignDriverJob queued (async)
- Event: OrderReady fired
- Customer notified: "Your order is ready! Driver will be assigned shortly"
- System automatically finds and assigns best driver
```

#### 5️⃣ Reject Pending Order
```http
PATCH /api/employee/orders/2/reject
Content-Type: application/json
Authorization: Bearer {employee_token}

{
  "reason": "Items not available"
}

Response (200):
{
  "success": true,
  "data": {
    "id": 2,
    "status": "rejected"
  },
  "message": "Order rejected successfully"
}

Behind scenes:
- Order status: PENDING → REJECTED
- Payment refunded (100%)
- Customer notified
```

---

### DRIVER ENDPOINTS

#### 1️⃣ Get My Deliveries (Assigned)
```http
GET /api/driver/deliveries
Authorization: Bearer {driver_token}

Response (200):
{
  "success": true,
  "data": [
    {
      "id": 100,
      "delivery_status": "assigned",
      "order": {
        "order_number": "ORD-20260422-ABC123",
        "customer": {
          "name": "John Doe",
          "phone": "+1234567890"
        },
        "branch": {
          "name": "Downtown Branch",
          "address": "123 Main St"
        },
        "items": [
          {
            "name": "Burger",
            "quantity": 2
          }
        ]
      }
    }
  ]
}
```

#### 2️⃣ Get Delivery Details
```http
GET /api/driver/deliveries/100
Authorization: Bearer {driver_token}

Response (200):
{
  "success": true,
  "data": {
    "id": 100,
    "delivery_status": "assigned",
    "assigned_at": "2026-04-22T10:45:00Z",
    "order": {
      "order_number": "ORD-20260422-ABC123",
      "customer": {
        "name": "John Doe",
        "phone": "+1234567890"
      },
      "branch": {
        "name": "Downtown Branch",
        "address": "123 Main St",
        "phone": "+1111111111"
      },
      "deliveryAddress": {
        "address": "456 Oak Ave, Apt 5B",
        "latitude": 40.7128,
        "longitude": -74.0060
      },
      "items": [
        {
          "name": "Burger",
          "quantity": 2
        }
      ]
    }
  }
}
```

#### 3️⃣ Accept Delivery
```http
PATCH /api/driver/deliveries/100/accept
Content-Type: application/json
Authorization: Bearer {driver_token}

{
  "notes": "On my way to branch"
}

Response (200):
{
  "success": true,
  "data": {
    "id": 100,
    "delivery_status": "accepted",
    "accepted_at": "2026-04-22T10:50:00Z"
  },
  "message": "Delivery accepted. Head to branch for pickup"
}

Behind scenes:
- Delivery status: ASSIGNED → ACCEPTED
- Driver marked as BUSY
- Event: DeliveryAccepted fired
```

#### 4️⃣ Reject Delivery
```http
PATCH /api/driver/deliveries/100/reject
Content-Type: application/json
Authorization: Bearer {driver_token}

{
  "reason": "Vehicle breakdown"
}

Response (200):
{
  "success": true,
  "data": {
    "id": 100,
    "delivery_status": "rejected"
  },
  "message": "Delivery rejected. Another driver will be assigned"
}

Behind scenes:
- Delivery status: ASSIGNED → REJECTED
- Driver removed from delivery
- AssignDriverJob requeued immediately
- Next available driver assigned automatically
- Customer notified: "Driver reassigned"
```

#### 5️⃣ Pick Up Order from Branch
```http
PATCH /api/driver/deliveries/100/pickup
Content-Type: application/json
Authorization: Bearer {driver_token}

{
  "notes": "Order picked up"
}

Response (200):
{
  "success": true,
  "data": {
    "id": 100,
    "delivery_status": "picked_up",
    "picked_up_at": "2026-04-22T10:55:00Z"
  },
  "message": "Order picked up. Start delivery to customer"
}

Behind scenes:
- Delivery status: ACCEPTED → PICKED_UP
- Order status: READY_FOR_PICKUP → PICKED_UP
- Event: OrderPickedUp fired
- Customer notified: "Your order is on the way!"
- Live tracking enabled
```

#### 6️⃣ Deliver Order to Customer (SUCCESS)
```http
PATCH /api/driver/deliveries/100/deliver
Content-Type: application/json
Authorization: Bearer {driver_token}

{
  "proof_image_url": "https://s3.amazonaws.com/proof_20260422_100.jpg",
  "delivery_notes": "Left at front door as requested"
}

Response (200):
{
  "success": true,
  "data": {
    "id": 100,
    "delivery_status": "delivered",
    "delivered_at": "2026-04-22T11:10:00Z"
  },
  "message": "Order delivered successfully! Thank you for the delivery"
}

Behind scenes:
- Delivery status: PICKED_UP → DELIVERED
- Order status: PICKED_UP → DELIVERED
- Payment status: PENDING → PAID
- Loyalty points: +38 points added to customer
- Event: OrderDelivered fired
- Customer notified: "Order delivered! Rate your experience"
- Driver marked as AVAILABLE
```

#### 7️⃣ Mark Delivery as FAILED
```http
PATCH /api/driver/deliveries/100/fail
Content-Type: application/json
Authorization: Bearer {driver_token}

{
  "reason": "Customer not home, building locked"
}

Response (200):
{
  "success": true,
  "data": {
    "id": 100,
    "delivery_status": "unassigned",
    "retry_attempt": 1,
    "scheduled_retry_at": "2026-04-22T13:10:00Z"
  },
  "message": "Delivery marked as failed. Retry 1 scheduled for 13:10"
}

Behind scenes (if retry_attempt < 3):
- Delivery status: PICKED_UP → UNASSIGNED
- retry_attempt: incremented
- scheduled_retry_at: set based on attempt
- Driver removed from delivery
- AssignDriverJob requeued with delay
- Customer notified: "Delivery failed, retrying at 13:10"
- Order marked as FAILED_DELIVERY (temporarily)

Behind scenes (if retry_attempt >= 3):
- Delivery status: PICKED_UP → FAILED
- Order status: PICKED_UP → FAILED_DELIVERY (final)
- Customer notified with options: "Refund / Reschedule / Pick up from branch"
- Admin notified for manual intervention
```

---

## 🔄 EXAMPLE WORKFLOWS

### Complete Successful Order Journey

```
1. Customer: POST /api/customer/orders
   → Order created as PENDING
   → Validated (branch open, items available)
   → AUTO-CONFIRMED to CONFIRMED
   → Kitchen notified

2. Employee: PATCH /api/employee/orders/1/mark-preparing
   → Order: CONFIRMED → PREPARING
   → Customer notified: "Being prepared"

3. Employee: PATCH /api/employee/orders/1/mark-ready
   → Order: PREPARING → READY_FOR_PICKUP
   → Delivery created as UNASSIGNED
   → AssignDriverJob queued

4. System (Background): AssignDriverJob runs
   → Finds best driver (least busy)
   → Assigns delivery
   → Driver notified: "New delivery assigned"

5. Driver: PATCH /api/driver/deliveries/100/accept
   → Delivery: ASSIGNED → ACCEPTED
   → Heads to branch

6. Driver: PATCH /api/driver/deliveries/100/pickup
   → Delivery: ACCEPTED → PICKED_UP
   → Order: READY_FOR_PICKUP → PICKED_UP
   → Customer notified: "On the way!"

7. Driver: PATCH /api/driver/deliveries/100/deliver
   → Delivery: PICKED_UP → DELIVERED
   → Order: PICKED_UP → DELIVERED
   → Payment: PENDING → PAID
   → Loyalty points added
   → Customer notified: "Delivered + Rate us"

✅ ORDER COMPLETE
```

### Failed Delivery Retry Journey

```
1. Driver: PATCH /api/driver/deliveries/100/fail
   → Attempt 1 of 3
   → Scheduled retry in 2 hours

2. System: AssignDriverJob (after 2 hours)
   → Finds new driver
   → Assigns delivery again

3. If 2nd attempt also fails:
   → Attempt 2 of 3
   → Scheduled retry next day

4. If 3rd attempt also fails:
   → Delivery: FAILED
   → Order: FAILED_DELIVERY
   → Customer notified with options

✅ AUTO-RECOVERY COMPLETE
```

### Order Cancellation Journey

```
1. Order status: PREPARING
2. Customer: DELETE /api/customer/orders/1
   → Refund %: 80% (in preparing stage)
   → Payment: REFUNDED
   → Delivery: CANCELLED
   → All parties notified

✅ CANCELLATION COMPLETE WITH REFUND
```

---

## 🧪 Testing Checklist

- [ ] Customer can place order (auto-confirms)
- [ ] Employee can mark preparing
- [ ] Employee can mark ready (driver assignment queued)
- [ ] Driver gets assignment notification
- [ ] Driver can accept delivery
- [ ] Driver can pick up order
- [ ] Driver can deliver with proof
- [ ] Payment marked as paid
- [ ] Loyalty points added
- [ ] Customer can cancel (refund processed)
- [ ] Failed delivery triggers auto-retry
- [ ] All notifications sent

---

## 📝 Notes

- All timestamps are in UTC
- All prices are in decimal format (XX.XX)
- Images should be valid S3/CDN URLs
- Driver must be from same branch as order branch
- Only available drivers can be assigned
