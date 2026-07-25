<?php

namespace App\Services\Auth;

use App\Mail\VerifyEmail;
use App\Models\User;
use App\Models\VerificationChallenge;
use App\Traits\SendSms;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;

class AuthService
{
    use SendSms;

    public function __construct(private readonly VerificationChallengeService $challenges)
    {
    }

    public function register(array $data, string $roleHeader): bool
    {
        [$user, $code] = DB::transaction(function () use ($data, $roleHeader) {
            $user = User::create([
                'name' => $data['name'],
                'password' => Hash::make($data['password']),
                'address' => $data['address'],
                'phone' => $data['phone'],
            ]);
            $isUser = $roleHeader === 'user';

            $user->addMedia($data['image'])->toMediaCollection(
                $isUser ? 'users-images' : 'drivers-images'
            );

            $role = Role::where([
                'name' => $isUser ? 'user' : 'driver',
                'guard_name' => 'api',
            ])->first();

            $user->assignRole($role);

            return [$user, $this->challenges->issue(
                $user,
                VerificationChallenge::PURPOSE_PHONE_VERIFICATION,
                $user->phone
            )];
        });

        return $this->sendSms($user->phone, $code)->getStatus() === 0;
    }

    public function verifyUser(array $data): ?array
    {
        $user = $this->findUser($data);

        if (! $user || ! $this->challenges->verifyAndConsume(
            $user,
            $this->verificationPurpose($data['type']),
            $data['value'],
            $data['code']
        )) {
            return null;
        }

        $user->update([$this->activationColumn($data['type']) => true]);

        return [
            'user' => $user,
            'token' => $user->createToken('TAWSELA')->plainTextToken,
        ];
    }

    public function login(array $data): ?array
    {
        $user = $this->findUser($data);

        if (! $user || ! $user->password || ! Hash::check($data['password'], $user->password)) {
            return null;
        }

        if ($user->{$this->activationColumn($data['type'])}) {
            return [
                'activated' => true,
                'activation' => $user->is_active_phone,
                'token' => $user->createToken('TAWSELA')->plainTextToken,
            ];
        }

        $this->sendVerificationCode(
            $user,
            $this->verificationPurpose($data['type']),
            $data['type'],
            $data['value']
        );

        return ['activated' => false];
    }

    public function sendPasswordResetCode(array $data): bool
    {
        $user = $this->findUser($data);

        if (! $user) {
            return false;
        }

        $this->sendVerificationCode(
            $user,
            VerificationChallenge::PURPOSE_PASSWORD_RESET,
            $data['type'],
            $data['value']
        );

        return true;
    }

    public function isPasswordResetCodeValid(array $data): bool
    {
        $user = $this->findUser($data);

        return $user && $this->challenges->isValid(
            $user,
            VerificationChallenge::PURPOSE_PASSWORD_RESET,
            $data['value'],
            $data['code']
        );
    }

    public function resetPassword(array $data): bool
    {
        $user = $this->findUser($data);

        if (! $user) {
            return false;
        }

        return DB::transaction(function () use ($data, $user) {
            if (! $this->challenges->verifyAndConsume(
                $user,
                VerificationChallenge::PURPOSE_PASSWORD_RESET,
                $data['value'],
                $data['code']
            )) {
                return false;
            }

            $user->update(['password' => Hash::make($data['password'])]);
            $user->tokens()->delete();

            return true;
        });
    }

    private function findUser(array $data): ?User
    {
        return User::where($data['type'], $data['value'])->first();
    }

    private function verificationPurpose(string $type): string
    {
        return $type === 'email'
            ? VerificationChallenge::PURPOSE_EMAIL_VERIFICATION
            : VerificationChallenge::PURPOSE_PHONE_VERIFICATION;
    }

    private function activationColumn(string $type): string
    {
        return $type === 'email' ? 'is_active_email' : 'is_active_phone';
    }

    private function sendVerificationCode(User $user, string $purpose, string $type, string $destination): void
    {
        $code = $this->challenges->issue($user, $purpose, $destination);

        if ($type === 'email') {
            Mail::to($user->email)
                ->bcc('basmaelazony@gmail.com')
                ->send(new VerifyEmail($code));

            return;
        }

        $this->sendSms($user->phone, $code);
    }

    public function updateProfile(User $user, array $data)
    {
            DB::transaction(function () use ($data, $user) {
                $user->update([
                    'name' => $data['name'],
                    'address' => $data['address'],
                    'phone' => $data['phone'],
                    'national_number' => $data['national_number'] ?? null,
                ]);
                if (! $user->hasRole('driver')) {
                    $this->replaceMedia($user, $data['image'] ?? null, 'users-images');
                    return;
                }
                $vehicle = $user->vehicleDoc;
                $vehicle->update([
                    'car_type_id' => $data['car_type_id'],
                    'car_brand_id' => $data['car_brand_id'],
                    'car_color' => $data['car_color'],
                    'metal_plate_numbers' => $data['metal_plate_numbers'],
                    'model_year' => $data['model_year'],
                    'license_expire_date' => $data['license_date'],
                ]);
                $this->replaceMedia($user, $data['image'] ?? null, 'drivers-images');
                $this->replaceMedia($vehicle, $data['vehicle_license'] ?? null, 'vehicle_licenses');
                $this->replaceMedia($vehicle, $data['vehicle_license_behind'] ?? null, 'vehicle_licenses_behind');
                $this->replaceMedia($vehicle, $data['vehicle_inspection'] ?? null, 'vehicle_inspections');
                $this->replaceMedia($vehicle, $data['nationalId_image'] ?? null, 'nationalId_images');
                $this->replaceMedia($vehicle, $data['personal_image'] ?? null, 'personal_images');
                $this->replaceMedia($vehicle, $data['driving_license'] ?? null, 'driving_licenses');
                $this->replaceMedia($vehicle, $data['drug_analysis'] ?? null, 'Drug_analyses');
                $this->replaceMedia($vehicle, $data['criminal_record'] ?? null, 'criminal_records');
            });
            
            return true;
           
    }

    private function replaceMedia($model, mixed $media, string $collection): void
    {
        if (! $media) {
            return;
        }

        $model->clearMediaCollection($collection);
        $model->addMedia($media)->toMediaCollection($collection);
    }


}
