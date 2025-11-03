# Product Search Test Instructions

## Steps to Test

### 1. Open Browser Console
- Press `F12` or `Ctrl+Shift+I` to open Developer Tools
- Go to the **Console** tab
- Keep it open while testing

### 2. Navigate to Add Product Modal
- Go to Service Stock page
- Click "Add Product" button
- Modal should open

### 3. Watch for Console Messages
When you type in the product dropdown, you should see:
```
Filter event received: a
Filter event received: as
Executing search for: as
```

### 4. Test Scenarios

#### Scenario A: Type 1 Character
- Type "a" in the product field
- Console shows: `Filter event received: a`
- Dropdown shows: "Start typing to search all products in the system..."
- No API call is made (expected - minimum 2 characters)

#### Scenario B: Type 2+ Characters
- Type "as" in the product field
- Console shows:
  ```
  Filter event received: a
  Filter event received: as
  Executing search for: as
  ```
- Loading spinner appears: "🔄 Searching products..."
- After 1-2 seconds, dropdown populates with matching products
- If nothing appears, check Network tab for API errors

#### Scenario C: Clear Search
- Type "aspirin", get results
- Delete all text (clear the field)
- Console shows: `Filter event received: ` (empty)
- Dropdown clears
- Message changes to: "Start typing to search all products in the system..."

#### Scenario D: Click X Button
- Type "aspirin", get results
- Click the X (clear button)
- Dropdown clears completely
- Ready to search again

### 5. Network Tab Debugging

If products don't appear:
- Open **Network** tab in Developer Tools
- Type product name (2+ characters)
- Look for API request: `GET /api/pharmacy/products?search=...&per_page=50`
- Check the response:
  - Should have `"success": true`
  - Should have `"data": [...]` with products
  - If error, note the status code and message

### 6. Expected API Response Format

```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Aspirin 500mg",
      "code": "ASP-001",
      "category": "Pain Relief",
      "forme": "Tablet",
      "boite_de": 100,
      ...
    }
  ]
}
```

## Common Issues & Solutions

| Issue | Console Message | Solution |
|-------|-----------------|----------|
| No products show | No API call | Check if typing 2+ characters |
| | Executing search but no results | Product might not exist in DB |
| | Network error 404 | API endpoint missing |
| | Network error 500 | Server error - check Laravel logs |
| Typing is slow | Executing search takes time | Normal for first request |
| Can't clear | X button doesn't work | Try clicking X button twice |

## If Still Not Working

1. **Clear Browser Cache**
   - `Ctrl+Shift+Delete` → Select "All time" → Clear

2. **Hard Refresh Page**
   - `Ctrl+Shift+R` (or Cmd+Shift+R on Mac)

3. **Check Browser Console for Errors**
   - Look for red errors in console
   - Screenshot and report

4. **Check Server Logs**
   ```bash
   tail -f /home/administrator/www/HIS/storage/logs/laravel.log
   ```

5. **Test API Directly**
   ```bash
   curl "http://localhost:9000/api/pharmacy/products?search=aspirin&per_page=50"
   ```

## Test Completion Checklist

- [ ] Typing 1 character: No search triggered
- [ ] Typing 2+ characters: API called and results shown
- [ ] Clear field: Dropdown clears and shows guidance
- [ ] Click X button: Selection cleared
- [ ] Product appears in dropdown: Can select it
- [ ] Selected product details show: Form, Category, Box info display
- [ ] Complete add to stock workflow: Successfully adds product

---

**If all tests pass, the feature is working correctly!** ✅
