# Seahub Test 1 – Backend Challenge

This repository contains a small public **Drupal 10 backend challenge**, inspired by real-world patterns:

- Custom entities
- Soft delete
- Backend filtering
- API endpoint
- Automated tests

---

## 🚀 Quick Start (DDEV)

```bash
ddev start
make install
make test
```

---

## 📋 What You Must Deliver (Pull Request)

### 1️⃣ Implement Soft-Delete Filtering

- `deleted_at` is a timestamp field.
- Work Orders with `deleted_at != NULL` **must be excluded by default**.
- Provide an **escape hatch** to include deleted items (a query tag is already provided).

---

### 2️⃣ Create an Admin View

Create an admin listing for **Work Orders** with:

**Fields:**
- Status
- Assigned To
- Created

**Filters:**
- `status`
- `assigned_to`

**Behavior:**
- Exclude soft-deleted items by default.
- Provide an option to include deleted items
  - You may use an exposed filter **or** a separate display.
- Provide an escape hatch using the query tag: `seahub_work_orders_include_deleted`.
- Hint: tags may be added after getQuery(), so implement the default filter at the correct layer.

---

### 3️⃣ API Endpoint

Implement:

```
GET /api/work-orders
```

**Requirements:**

- Supports filters:
  - `status`
  - `assigned_to`
- Supports pagination:
  - `page`
  - `limit`
- Must exclude soft-deleted records by default.

---

### 4️⃣ Tests

- Make the provided **Kernel test** pass.
- Add **at least one additional test** (Kernel or Functional).

---

## ✅ Deliverable Expectations

Your PR should include:

- Clean, readable code
- Clear description of decisions and tradeoffs
- Code style aligned with **Drupal standards**
- Proper Dependency Injection
  - ❌ No `\Drupal::` calls inside services or controllers
  - ✅ Use constructor DI
