# NexCRM Flutter Mobile App REST API Documentation (v1)

**Production Base URL:** `https://demoerp.nexgeno.cloud/api/v1`

---

## 🔑 Authentication Architecture

The API uses **JWT (JSON Web Token)** for stateless, secure session handling.

1. When **Staff** or **Customer** logs in, the API returns a JWT `token`.
2. For all subsequent requests, include the token in the HTTP request headers:
   ```http
   Authorization: Bearer <your_jwt_token>
   ```
3. Tokens remain valid for **30 days** (`2592000` seconds).

---

## 📦 Postman Collection Import

A ready-to-use Postman collection is located at:
`modules/crm_mobile_api/docs/NexCRM_Mobile_API.postman_collection.json`

To use it:
1. Open **Postman** $\rightarrow$ Click **Import**.
2. Select the `NexCRM_Mobile_API.postman_collection.json` file.
3. When you run `Staff Login` or `Customer Login`, a pre-built test script **automatically extracts the token and saves it to the `{{token}}` variable**. All subsequent requests will work automatically!

---

## 1. Authentication Endpoints

### 1.1 Staff Login
Authenticates a CRM staff member with their email and password.

* **Method:** `POST`
* **Endpoint:** `/auth/staff/login`
* **Headers:** `Content-Type: application/json`
* **Request Body:**
  ```json
  {
    "email": "staff@example.com",
    "password": "your_password"
  }
  ```
* **Success Response (`200 OK`):**
  ```json
  {
    "status": true,
    "message": "Staff login successful.",
    "data": {
      "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
      "expires_in": 2592000,
      "user": {
        "id": 8,
        "role": "staff",
        "firstname": "Karan",
        "lastname": "Malhotra",
        "email": "karan.sales@nexgeno.in",
        "phone": "+919876543210",
        "is_admin": true,
        "profile_image": "https://demoerp.nexgeno.cloud/uploads/staff_profile_images/8/small_image.jpg"
      }
    }
  }
  ```
* **Error Response (`401 Unauthorized`):**
  ```json
  {
    "status": false,
    "message": "Invalid email or password."
  }
  ```

---

### 1.2 Customer Login
Authenticates a client contact using their email and password.

* **Method:** `POST`
* **Endpoint:** `/auth/customer/login`
* **Headers:** `Content-Type: application/json`
* **Request Body:**
  ```json
  {
    "email": "customer@company.com",
    "password": "your_password"
  }
  ```
* **Success Response (`200 OK`):**
  ```json
  {
    "status": true,
    "message": "Customer login successful.",
    "data": {
      "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
      "expires_in": 2592000,
      "user": {
        "id": 3,
        "client_id": 2,
        "company": "Nova Organics Pvt. Ltd.",
        "role": "customer",
        "firstname": "Sneha",
        "lastname": "Nair",
        "email": "sneha.nair@aranya.com",
        "phone": "+919123456789",
        "title": "Operations Manager",
        "profile_image": "https://demoerp.nexgeno.cloud/uploads/client_profile_images/3/small_image.jpg"
      }
    }
  }
  ```

---

### 1.3 Current Profile Info
Fetches user details based on the provided token.

* **Method:** `GET`
* **Endpoint:** `/auth/me`
* **Headers:** `Authorization: Bearer <token>`
* **Success Response (`200 OK`):**
  ```json
  {
    "status": true,
    "data": {
      "id": 8,
      "role": "staff",
      "firstname": "Karan",
      "lastname": "Malhotra",
      "email": "karan.sales@nexgeno.in",
      "phone": "+919876543210",
      "is_admin": true,
      "profile_image": "..."
    }
  }
  ```

---

## 2. Dropdown / Form Options API

### 2.1 Get Ticket Metadata
Returns system dropdown options for ticket statuses, priorities, departments, and services. Useful on app launch or screen init.

* **Method:** `GET`
* **Endpoint:** `/tickets/meta`
* **Headers:** `Authorization: Bearer <token>`
* **Success Response (`200 OK`):**
  ```json
  {
    "status": true,
    "data": {
      "statuses": [
        { "id": 1, "name": "Open", "color": "#ff2d42" },
        { "id": 2, "name": "In Progress", "color": "#22c55e" },
        { "id": 3, "name": "Answered", "color": "#2563eb" },
        { "id": 4, "name": "On Hold", "color": "#64748b" },
        { "id": 5, "name": "Closed", "color": "#03a9f4" }
      ],
      "priorities": [
        { "id": 1, "name": "Low" },
        { "id": 2, "name": "Medium" },
        { "id": 3, "name": "High" }
      ],
      "departments": [
        { "id": 1, "name": "Support" },
        { "id": 3, "name": "Development" }
      ],
      "services": [
        { "id": 1, "name": "Camera malfunction" }
      ]
    }
  }
  ```

---

## 3. Customer Ticket APIs

> All customer endpoints enforce company isolation. Customers only see tickets belonging to their own organization.

### 3.1 List Customer Tickets
* **Method:** `GET`
* **Endpoint:** `/customer/tickets`
* **Query Parameters:**
  * `page` (integer, default `1`)
  * `limit` (integer, default `20`)
  * `status` (integer, optional status ID)
  * `q` (string, optional search query)
* **Headers:** `Authorization: Bearer <token>`
* **Success Response (`200 OK`):**
  ```json
  {
    "status": true,
    "data": {
      "total": 2,
      "page": 1,
      "limit": 20,
      "total_pages": 1,
      "tickets": [
        {
          "id": 3,
          "subject": "Maintenance Urgent",
          "status_id": 2,
          "status_name": "In Progress",
          "status_color": "#22c55e",
          "priority_id": 2,
          "priority_name": "Medium",
          "department_name": "Support",
          "service_name": null,
          "created_at": "2025-07-04 19:04:28",
          "last_reply": "2025-07-04 19:05:43"
        }
      ]
    }
  }
  ```

---

### 3.2 View Ticket Details & Conversation
* **Method:** `GET`
* **Endpoint:** `/customer/tickets/{id}`
* **Headers:** `Authorization: Bearer <token>`
* **Success Response (`200 OK`):**
  ```json
  {
    "status": true,
    "data": {
      "id": 3,
      "subject": "Maintenance Urgent",
      "message": "Our server room air conditioning has stopped working.",
      "status_id": 2,
      "status_name": "In Progress",
      "status_color": "#22c55e",
      "priority_id": 2,
      "priority_name": "Medium",
      "department_name": "Support",
      "service_name": null,
      "created_at": "2025-07-04 19:04:28",
      "last_reply": "2025-07-04 19:05:43",
      "attachments": [
        {
          "id": 12,
          "filename": "ac_panel_error.jpg",
          "filetype": "image/jpeg",
          "url": "https://demoerp.nexgeno.cloud/download/file/ticket/12"
        }
      ],
      "replies": [
        {
          "id": 4,
          "message": "We have dispatched a technician. ETA 45 minutes.",
          "date": "2025-07-04 19:05:43",
          "is_staff": true,
          "sender_name": "Support Team",
          "profile_image": "https://demoerp.nexgeno.cloud/uploads/staff_profile_images/8/small_image.jpg",
          "attachments": []
        }
      ]
    }
  }
  ```

---

### 3.3 Create New Ticket
* **Method:** `POST`
* **Endpoint:** `/customer/tickets`
* **Headers:**
  * `Authorization: Bearer <token>`
  * `Content-Type: application/json`
* **Request Body:**
  ```json
  {
    "subject": "Network outage in office",
    "message": "The main router stopped working around 3 PM.",
    "department": 1,
    "priority": 2,
    "service": 1
  }
  ```
* **Success Response (`201 Created`):**
  ```json
  {
    "status": true,
    "message": "Ticket created successfully.",
    "data": {
      "ticket_id": 14
    }
  }
  ```

---

### 3.4 Post Customer Reply
* **Method:** `POST`
* **Endpoint:** `/customer/tickets/{id}/reply`
* **Headers:**
  * `Authorization: Bearer <token>`
  * `Content-Type: application/json`
* **Request Body:**
  ```json
  {
    "message": "Thank you, the technician has arrived."
  }
  ```
* **Success Response (`201 Created`):**
  ```json
  {
    "status": true,
    "message": "Reply posted successfully.",
    "data": {
      "reply_id": 19
    }
  }
  ```

---

## 4. Staff Ticket APIs

### 4.1 List Staff Tickets
* **Method:** `GET`
* **Endpoint:** `/staff/tickets`
* **Query Parameters:**
  * `assigned`: `'me'` (assigned to current staff) or `'all'`
  * `status`: Status ID filter
  * `priority`: Priority ID filter
  * `department`: Department ID filter
  * `page`: Page number (default `1`)
  * `limit`: Items per page (default `20`)
  * `q`: Search keyword (searches subject, message, contact, company)
* **Headers:** `Authorization: Bearer <token>`
* **Success Response (`200 OK`):**
  ```json
  {
    "status": true,
    "data": {
      "total": 1,
      "page": 1,
      "limit": 20,
      "total_pages": 1,
      "tickets": [
        {
          "id": 11,
          "subject": "Camera offline in warehouse",
          "status_id": 1,
          "status_name": "Open",
          "status_color": "#ff2d42",
          "priority_id": 3,
          "priority_name": "High",
          "department_name": "Support",
          "service_name": "Camera malfunction",
          "company": "Nova Organics Pvt. Ltd.",
          "contact_name": "Sneha Nair",
          "assigned_id": 8,
          "assigned_name": "Karan Malhotra",
          "created_at": "2026-09-18 11:34:55",
          "last_reply": null
        }
      ]
    }
  }
  ```

---

### 4.2 View Staff Ticket Details
* **Method:** `GET`
* **Endpoint:** `/staff/tickets/{id}`
* **Headers:** `Authorization: Bearer <token>`

---

### 4.3 Update Ticket Status
* **Method:** `POST` or `PUT`
* **Endpoint:** `/staff/tickets/{id}/status`
* **Headers:**
  * `Authorization: Bearer <token>`
  * `Content-Type: application/json`
* **Request Body:**
  ```json
  {
    "status_id": 2
  }
  ```
* **Success Response (`200 OK`):**
  ```json
  {
    "status": true,
    "message": "Ticket status updated successfully.",
    "data": {
      "ticket_id": 11,
      "status_id": 2,
      "status_name": "In Progress"
    }
  }
  ```

---

### 4.4 Staff Post Reply
* **Method:** `POST`
* **Endpoint:** `/staff/tickets/{id}/reply`
* **Headers:**
  * `Authorization: Bearer <token>`
  * `Content-Type: application/json`
* **Request Body:**
  ```json
  {
    "message": "The camera lens has been replaced and testing has completed."
  }
  ```
* **Success Response (`201 Created`):**
  ```json
  {
    "status": true,
    "message": "Reply posted successfully.",
    "data": {
      "reply_id": 20
    }
  }
  ```

---

### 4.5 Upload Service Report or Document
* **Method:** `POST`
* **Endpoint:** `/staff/tickets/{id}/attachments`
* **Headers:**
  * `Authorization: Bearer <token>`
  * `Content-Type: multipart/form-data`
* **Body Form-Data:**
  * `file`: (Select file from camera/gallery or file picker)
* **Success Response (`200 OK`):**
  ```json
  {
    "status": true,
    "message": "Attachment uploaded successfully.",
    "data": {
      "attachments": [
        {
          "id": 15,
          "filename": "service_report_oct14.pdf",
          "filetype": "application/pdf",
          "url": "https://demoerp.nexgeno.cloud/download/file/ticket/15"
        }
      ]
    }
  }
  ```

---

## 5. HTTP Status Code Guidelines

| Code | Meaning | Description |
| :--- | :--- | :--- |
| **`200 OK`** | Success | Request succeeded. |
| **`201 Created`** | Created | Resource (ticket, reply) successfully created. |
| **`400 Bad Request`** | Malformed | Request payload could not be parsed. |
| **`401 Unauthorized`** | Auth Failed | Missing, expired, or invalid Bearer token. |
| **`403 Forbidden`** | Permission Denied | Role not authorized (e.g. customer hitting staff endpoint). |
| **`404 Not Found`** | Not Found | Requested ticket does not exist or belongs to another company. |
| **`422 Unprocessable`**| Validation Error | Missing required fields (e.g. missing subject or password). |
| **`500 Internal Error`**| Server Error | Unexpected database or server exception. |
