<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Template;
use App\Models\Placeholder;
use App\Models\Doctor;
use App\Models\Attribute;
use Illuminate\Support\Facades\DB;
use App\Models\PlaceholderTemplate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Reader\Xls\RC4;

class TemplateController extends Controller
{
    /**
     * Display a listing of the templates.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $templates = Template::with(['doctor', 'placeholders'])->where('folder_id' ,$request->folder_id)->get();
        return response()->json([
            'status' => 'success',
            'data' => $templates
        ]);
    }

    /**
     * Store a newly created template in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */

public function store(Request $request)
{
    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'description' => 'nullable|string',
        'content' => 'required|string',
        'doctor_id' => 'nullable|exists:doctors,id',
        'mime_type' => 'required|string',
        'folder_id' => 'required|exists:folders,id',
        'placeholders' => 'nullable|array',
    ]);

    // Get doctor info
    $doctorInfo = Doctor::with(['user'])->findOrFail($validated['doctor_id'] ?? 1);
    $doctorName = $doctorInfo->user->name;
    
    // Store the content
    $content = $validated['content'];
    $validated['content'] = $content;

    // Remove placeholders from validated data
    $placeholders = $validated['placeholders'] ?? [];
    unset($validated['placeholders']);

    DB::beginTransaction();
    try {
        // Create the template
        $template = Template::create($validated);

        // Process custom placeholders
        foreach ($placeholders as $placeholderText) {
            if (str_starts_with($placeholderText, '{{custom.')) {
                // Extract the custom attribute name
                $attributeName = trim(str_replace(['{{custom.', '}}'], '', $placeholderText));

                // Get or create the custom placeholder for this doctor
                $customPlaceholder = Placeholder::firstOrCreate(
                    [
                        'name' => 'custom',
                        'doctor_id' => $validated['doctor_id']
                    ],
                    [
                        'description' => 'Custom placeholders for Dr. ' . $doctorName
                    ]
                );

                // Create the custom attribute
                $attribute = Attribute::firstOrCreate(
                    [
                        'placeholder_id' => $customPlaceholder->id,
                        'name' => $attributeName,
                    ],
                    [
                        'description' => 'Custom attribute created by Dr. ' . $doctorName,
                        'value' => ''
                    ]
                );

                // Create the placeholder-template relationship
                PlaceholderTemplate::create([
                    'template_id' => $template->id,
                    'placeholder_id' => $customPlaceholder->id,
                    'attribute_id' => $attribute->id
                ]);
            }
        }

        DB::commit();

        return response()->json([
            'status' => 'success',
            'message' => 'Template created successfully',
            'data' => $template->load('doctor', 'placeholders')
        ], 201);

    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'status' => 'error',
            'message' => 'Failed to save template: ' . $e->getMessage()
        ], 500);
    }
}
    /**
     * Display the specified template.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $template = Template::with(['doctor', 'placeholders'])->findOrFail($id);
        
        return response()->json([
            'status' => 'success',
            'data' => $template
        ]);
    }

    /**
     * Update the specified template in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
  public function update(Request $request, $id)
    {
        // Find the template or throw a 404
        $template = Template::findOrFail($id);

        // Validate incoming request data, making 'name' required like in store
        $validated = $request->validate([
            'name' => 'nullable|string|max:255', // Changed to required
            'description' => 'nullable|string',
            'content' => 'nullable|string', // Changed to required
            'doctor_id' => 'nullable|exists:doctors,id',
            'mime_type' => 'nullable|string', // Changed to required
            'placeholders' => 'nullable|array',
        ]);

        // Get doctor info for custom placeholder descriptions, similar to store
        // Default to doctor_id 1 if not provided, or handle as per your application's logic
        $doctorInfo = Doctor::with(['user'])->find($validated['doctor_id'] ?? null);
        $doctorName = $doctorInfo ? $doctorInfo->user->name : 'Unknown Doctor'; // Fallback if doctor not found

        // Remove placeholders from validated data before updating the template itself
        $placeholders = $validated['placeholders'] ?? [];
        unset($validated['placeholders']);

        DB::beginTransaction(); // Start database transaction

        try {
            // Handle file upload if present
            if ($request->hasFile('file')) {
                // Delete old file if exists
                if ($template->file_path) {
                    Storage::disk('public')->delete($template->file_path);
                }
                
                $file = $request->file('file');
                $fileName = time() . '_' . Str::slug($request->name) . '.' . $file->getClientOriginalExtension();
                $filePath = $file->storeAs('templates', $fileName, 'public');
                $validated['file_path'] = $filePath;
            }

            // Update the template with validated data
            $template->update($validated);

            // Process and update placeholders if provided
            if (isset($request->placeholders)) {
                // Delete existing placeholder associations for this template
                PlaceholderTemplate::where('template_id', $template->id)->delete();
                
                // Create new placeholder associations based on the incoming data
                foreach ($placeholders as $placeholderText) {
                    $placeholderText = trim($placeholderText);

                    // Replicate custom placeholder handling from the store function
                    if (str_starts_with($placeholderText, '{{custom.')) {
                        // Extract the custom attribute name
                        $attributeName = trim(str_replace(['{{custom.', '}}'], '', $placeholderText));

                        // Get or create the custom placeholder for this doctor
                        $customPlaceholder = Placeholder::firstOrCreate(
                            [
                                'name' => 'custom', // Generic name for custom type
                                'doctor_id' => $validated['doctor_id'] ?? null // Associate with doctor if available
                            ],
                            [
                                'description' => 'Custom placeholders for Dr. ' . $doctorName,
                                'type' => 'custom',
                                'field' => 'custom' // Generic field for custom type
                            ]
                        );

                        // Create the custom attribute
                        $attribute = Attribute::firstOrCreate(
                            [
                                'placeholder_id' => $customPlaceholder->id,
                                'name' => $attributeName, // The specific attribute name (e.g., 'patient_signature')
                            ],
                            [
                                'description' => 'Custom attribute created by Dr. ' . $doctorName,
                                'value' => '' // Default empty value
                            ]
                        );

                        // Create the placeholder-template relationship
                        PlaceholderTemplate::create([
                            'template_id' => $template->id,
                            'placeholder_id' => $customPlaceholder->id,
                            'attribute_id' => $attribute->id // Link to the specific attribute
                        ]);
                    } else {
                        // If you also need to handle other types of placeholders (e.g., patient.name)
                        // that don't start with '{{custom.', you would add that logic here.
                        // Currently, this updated function specifically mimics the 'store' which focuses on 'custom'.
                        // For example, if you wanted to maintain the old logic for non-custom placeholders:
                        /*
                        $content = str_replace(['{{', '}}'], '', $placeholderText);
                        if (strpos($content, '.') !== false) {
                            list($type, $field) = explode('.', $content, 2);
                        } else {
                            $type = 'custom_generic'; // A different type to distinguish
                            $field = $content;
                        }
                        $placeholder = Placeholder::firstOrCreate(
                            ['value' => $placeholderText],
                            ['name' => $field, 'type' => $type, 'field' => $field]
                        );
                        PlaceholderTemplate::create([
                            'template_id' => $template->id,
                            'placeholder_id' => $placeholder->id
                        ]);
                        */
                    }
                }
            }

            DB::commit(); // Commit the transaction if all operations are successful

            // Return success response, loading relationships as in store
            return response()->json([
                'status' => 'success',
                'message' => 'Template updated successfully',
                'data' => $template->fresh()->load('doctor', 'placeholders')
            ]);

        } catch (\Exception $e) {
            DB::rollBack(); // Rollback the transaction on error
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update template: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified template from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $template = Template::findOrFail($id);
        
        // Delete file if exists
        if ($template->file_path) {
            Storage::disk('public')->delete($template->file_path);
        }
        
        // Delete placeholder associations
        PlaceholderTemplate::where('template_id', $template->id)->delete();
        
        // Delete the template
        $template->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Template deleted successfully'
        ]);
    }

    /**
     * Search for templates based on given parameters.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function search(Request $request)
    {
        $query = Template::query();

        if ($request->has('name')) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }

        if ($request->has('doctor_id')) {
            $query->where('doctor_id', $request->doctor_id);
        }
        
        $templates = $query->with(['doctor', 'placeholders'])->get();

        return response()->json([
            'status' => 'success',
            'data' => $templates
        ]);
    }
}
