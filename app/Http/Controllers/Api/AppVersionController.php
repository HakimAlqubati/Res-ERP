<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\AppVersion\StoreAppVersionRequest;
use App\Http\Requests\Api\AppVersion\UpdateAppVersionRequest;
use App\Http\Resources\Api\AppVersionResource;
use App\Models\AppVersion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AppVersionController extends Controller
{
    /**
     * Check if a new version is available for the client app.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function check(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'platform'     => ['required', 'string', 'in:' . implode(',', AppVersion::PLATFORMS)],
            'version_code' => ['required', 'integer', 'min:0'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => 'Validation error',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $platform = $request->input('platform');
        $currentVersionCode = (int) $request->input('version_code');

        $latestVersion = AppVersion::active()
            ->forPlatform($platform)
            ->orderByDesc('version_code')
            ->first();

        if (!$latestVersion) {
            return response()->json([
                'status'           => true,
                'update_available' => false,
                'force_update'     => false,
                'message'          => 'No active versions found for this platform.',
            ]);
        }

        $isUpdateAvailable = $latestVersion->version_code > $currentVersionCode;
        $isForceUpdate     = false;

        if ($isUpdateAvailable) {
            $isForceUpdate = (bool) $latestVersion->is_force_update
                || ($latestVersion->min_version_code && $currentVersionCode < $latestVersion->min_version_code);
        }

        return response()->json([
            'status'           => true,
            'update_available' => $isUpdateAvailable,
            'force_update'     => $isForceUpdate,
            'latest_version'   => new AppVersionResource($latestVersion),
        ]);
    }

    /**
     * Display a listing of the versions (CRUD).
     */
    public function index(Request $request)
    {
        $query = AppVersion::with('creator');

        if ($request->has('platform')) {
            $query->where('platform', $request->input('platform'));
        }

        if ($request->has('is_active')) {
            $query->where('is_active', filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN));
        }

        $perPage = (int) $request->input('per_page', 15);
        $versions = $query->latest('version_code')->paginate($perPage);

        return AppVersionResource::collection($versions);
    }

    /**
     * Store a newly created version.
     */
    public function store(StoreAppVersionRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['created_by'] = auth()->id();

        $version = AppVersion::create($data);

        return response()->json([
            'status'  => true,
            'message' => 'App version created successfully.',
            'data'    => new AppVersionResource($version),
        ], 201);
    }

    /**
     * Display the specified version.
     */
    public function show($id): JsonResponse
    {
        $version = AppVersion::with('creator')->findOrFail($id);

        return response()->json([
            'status' => true,
            'data'   => new AppVersionResource($version),
        ]);
    }

    /**
     * Update the specified version.
     */
    public function update(UpdateAppVersionRequest $request, $id): JsonResponse
    {
        $version = AppVersion::findOrFail($id);
        $version->update($request->validated());

        return response()->json([
            'status'  => true,
            'message' => 'App version updated successfully.',
            'data'    => new AppVersionResource($version),
        ]);
    }

    /**
     * Remove the specified version.
     */
    public function destroy($id): JsonResponse
    {
        $version = AppVersion::findOrFail($id);
        $version->delete();

        return response()->json([
            'status'  => true,
            'message' => 'App version deleted successfully.',
        ]);
    }
}
