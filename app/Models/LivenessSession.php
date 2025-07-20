<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LivenessSession extends Model
{
    protected $fillable = [
        'session_id',
        'employee_id',
        'employee_name',
        'face_id',
        'raw_name',
        'is_live',
        'confidence',
        'status',
        'audit_images_count',
        'attendance_result',
        'error',
    ];
    public static function createLivenessSession(array $data)
    {
        return self::create([
            'session_id'         => $data['session_id'] ?? null,
            'employee_id'        => $data['employee_id'] ?? null,
            'employee_name'      => $data['employee_name'] ?? null,
            'face_id'            => $data['face_id'] ?? null,
            'raw_name'           => $data['raw_name'] ?? null,
            'is_live'            => $data['is_live'] ?? false,
            'confidence'         => $data['confidence'] ?? null,
            'status'             => $data['status'] ?? 'FAILED',
            'audit_images_count' => $data['audit_images_count'] ?? null,
            'attendance_result'  => isset($data['attendance_result'])
            ? (is_array($data['attendance_result'])
                ? json_encode($data['attendance_result'])
                : $data['attendance_result'])
            : null,
            'error'              => $data['error'] ?? null,
        ]);
    }
}