## Payment Approval with File Upload Enhancement

### Overview
The payment system has been enhanced to support file uploads for card/cheque payments that require approval. This allows users to attach supporting documents (receipts, invoices, etc.) when requesting payment approval.

### Features Added

#### 1. File Upload in Payment Approval Modal
- **File Types Supported**: Images (JPEG, PNG, GIF) and PDF files
- **File Size Limit**: Maximum 5MB per file
- **Validation**: Client-side validation for file type and size
- **User Experience**: Drag-and-drop style file selection with preview

#### 2. Enhanced Approval Dashboard
- **File Display**: Shows attached files with appropriate icons
- **File Download**: Direct links to view/download attachments
- **File Update**: Approvers can replace/update the attached file
- **File Information**: Displays file name, size, and type

#### 3. Backend Integration
- **Multipart Form Data**: Uses FormData for file uploads
- **File Storage**: Files are stored securely on the server
- **Database Tracking**: File metadata stored in database
- **API Endpoints**: New endpoints for file upload and update

### User Flow

#### For Payment Requestor:
1. Select card/cheque payment method
2. Enter payment details and select approver
3. **NEW**: Upload supporting document (optional but recommended)
4. Submit approval request
5. File is attached to the approval request

#### For Approver:
1. View pending approval requests in dashboard
2. **NEW**: See attached files with download links
3. **NEW**: Update/replace files if needed
4. Review payment details and attached documents
5. Approve or reject the request

### Technical Implementation

#### Frontend Changes:
- `PaymentApprovalModal.vue`: Added file upload functionality
- `ApprovalDashboard.vue`: Added file display and update capabilities
- `CaissePatientPayment.vue`: Updated to handle attachment data

#### Key Components:
- File validation (type and size)
- FormData for multipart uploads
- File preview and management
- Error handling for upload failures

#### API Integration:
- POST `/api/transaction-bank-requests` (with file attachment)
- POST `/api/transaction-bank-requests/{id}/update-attachment`
- File storage and retrieval endpoints

### Benefits

1. **Better Documentation**: All payments have supporting evidence
2. **Audit Trail**: Complete record of payment requests and approvals
3. **Compliance**: Meets regulatory requirements for financial transactions
4. **User Experience**: Streamlined approval process with visual evidence
5. **Security**: Secure file storage with access controls

### Usage Instructions

#### Uploading Files:
1. When making a card/cheque payment, the approval modal will appear
2. Click the upload area or drag files to attach
3. Select image or PDF files (max 5MB)
4. Add notes if needed
5. Submit the approval request

#### Managing Files in Dashboard:
1. View pending requests with file attachments
2. Click file links to download/view
3. Use "Update" button to replace files
4. Approve/reject requests as usual

This enhancement significantly improves the payment approval workflow by providing complete documentation and evidence for all financial transactions.</content>
<parameter name="filePath">d:\Projects\AppointmentSystem\AppointmentSystem-main\PAYMENT_FILE_UPLOAD_FEATURE.md
