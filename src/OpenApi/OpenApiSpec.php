<?php

/**
 * @OA\Info(
 *     title="Form Builder API",
 *     version="1.0.0",
 *     description="A comprehensive API for building, managing, and processing dynamic forms with payment integration",
 *     @OA\Contact(
 *         email="admin@bsmmupathalumni.org",
 *         name="API Support"
 *     ),
 *     @OA\License(
 *         name="MIT",
 *         url="https://opensource.org/licenses/MIT"
 *     )
 * )
 * 
 * @OA\Server(
 *     url="http://localhost:5000",
 *     description="Development server"
 * )
 * 
 * @OA\Server(
 *     url="https://api.bsmmupathalumni.org",
 *     description="Production server"
 * )
 * 
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="JWT Authorization header using the Bearer scheme. Example: 'Authorization: Bearer {token}'"
 * )
 * 
 * @OA\Tag(
 *     name="Authentication",
 *     description="User authentication and authorization"
 * )
 * 
 * @OA\Tag(
 *     name="Forms",
 *     description="Form creation and management"
 * )
 * 
 * @OA\Tag(
 *     name="Submissions",
 *     description="Form submission handling"
 * )
 * 
 * @OA\Tag(
 *     name="Payments",
 *     description="Payment processing and management"
 * )
 * 
 * @OA\Tag(
 *     name="File Upload",
 *     description="File upload and management"
 * )
 * 
 * @OA\Tag(
 *     name="Export",
 *     description="Data export functionality"
 * )
 * 
 * @OA\Tag(
 *     name="Admin",
 *     description="Administrative functions"
 * )
 * 
 * @OA\Tag(
 *     name="Settings",
 *     description="Application settings management"
 * )
 */

// Common schemas used across the API

/**
 * @OA\Schema(
 *     schema="User",
 *     type="object",
 *     required={"id", "name", "email", "role", "createdAt"},
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="John Doe"),
 *     @OA\Property(property="email", type="string", format="email", example="john@example.com"),
 *     @OA\Property(property="role", type="string", enum={"user", "admin", "super_admin", "form_manager", "payment_approver", "submission_viewer", "submission_editor", "notification_manager"}, example="user"),
 *     @OA\Property(property="permissions", type="array", @OA\Items(type="string")),
 *     @OA\Property(property="isActive", type="boolean", example=true),
 *     @OA\Property(property="createdAt", type="string", format="date-time"),
 *     @OA\Property(property="updatedAt", type="string", format="date-time")
 * )
 */

/**
 * @OA\Schema(
 *     schema="Form",
 *     type="object",
 *     required={"id", "title", "fields", "createdBy"},
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="title", type="string", example="Contact Form"),
 *     @OA\Property(property="description", type="string", nullable=true),
 *     @OA\Property(
 *         property="fields",
 *         type="array",
 *         @OA\Items(
 *             type="object",
 *             @OA\Property(property="type", type="string", example="text"),
 *             @OA\Property(property="name", type="string", example="full_name"),
 *             @OA\Property(property="label", type="string", example="Full Name"),
 *             @OA\Property(property="required", type="boolean", example=true),
 *             @OA\Property(property="placeholder", type="string", example="Enter your full name")
 *         )
 *     ),
 *     @OA\Property(property="isActive", type="boolean", example=true),
 *     @OA\Property(property="allowEditing", type="boolean", example=false),
 *     @OA\Property(property="createdBy", type="integer", example=1),
 *     @OA\Property(property="submissionCount", type="integer", example=25),
 *     @OA\Property(property="customUrl", type="string", nullable=true),
 *     @OA\Property(property="settings", type="object"),
 *     @OA\Property(property="analytics", type="object"),
 *     @OA\Property(property="createdAt", type="string", format="date-time"),
 *     @OA\Property(property="updatedAt", type="string", format="date-time")
 * )
 */

/**
 * @OA\Schema(
 *     schema="Submission",
 *     type="object",
 *     required={"id", "uniqueId", "formId", "data", "status"},
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="uniqueId", type="string", example="sub_1234567890"),
 *     @OA\Property(property="editCode", type="string", example="EDIT123"),
 *     @OA\Property(property="formId", type="integer", example=1),
 *     @OA\Property(property="data", type="object"),
 *     @OA\Property(property="submitterInfo", type="object"),
 *     @OA\Property(property="paymentInfo", type="object"),
 *     @OA\Property(property="status", type="string", enum={"pending", "completed", "failed"}, example="pending"),
 *     @OA\Property(property="files", type="array", @OA\Items(type="string")),
 *     @OA\Property(property="adminNotes", type="string", nullable=true),
 *     @OA\Property(property="editHistory", type="array", @OA\Items(type="object")),
 *     @OA\Property(property="paymentMethod", type="string", enum={"card", "stripe", "bkash", "bank_transfer"}, nullable=true),
 *     @OA\Property(property="createdAt", type="string", format="date-time"),
 *     @OA\Property(property="updatedAt", type="string", format="date-time")
 * )
 */

/**
 * @OA\Schema(
 *     schema="ApiResponse",
 *     type="object",
 *     required={"success", "message"},
 *     @OA\Property(property="success", type="boolean"),
 *     @OA\Property(property="message", type="string"),
 *     @OA\Property(property="data", type="object", nullable=true),
 *     @OA\Property(property="errors", type="object", nullable=true)
 * )
 */

/**
 * @OA\Schema(
 *     schema="ValidationError",
 *     type="object",
 *     required={"success", "message", "errors"},
 *     @OA\Property(property="success", type="boolean", example=false),
 *     @OA\Property(property="message", type="string", example="Validation failed"),
 *     @OA\Property(
 *         property="errors",
 *         type="object",
 *         additionalProperties={
 *             "type": "array",
 *             "items": {"type": "string"}
 *         },
 *         example={
 *             "email": {"The email field is required"},
 *             "password": {"The password must be at least 6 characters"}
 *         }
 *     )
 * )
 */

/**
 * @OA\Schema(
 *     schema="UnauthorizedError",
 *     type="object",
 *     @OA\Property(property="success", type="boolean", example=false),
 *     @OA\Property(property="message", type="string", example="Unauthorized")
 * )
 */

/**
 * @OA\Schema(
 *     schema="ForbiddenError",
 *     type="object",
 *     @OA\Property(property="success", type="boolean", example=false),
 *     @OA\Property(property="message", type="string", example="Access denied")
 * )
 */

/**
 * @OA\Schema(
 *     schema="NotFoundError",
 *     type="object",
 *     @OA\Property(property="success", type="boolean", example=false),
 *     @OA\Property(property="message", type="string", example="Resource not found")
 * )
 */

/**
 * @OA\Schema(
 *     schema="ServerError",
 *     type="object",
 *     @OA\Property(property="success", type="boolean", example=false),
 *     @OA\Property(property="message", type="string", example="Internal server error")
 * )
 */
