<?php

namespace index;

use ReallifeKip\ImmutableBase\Attributes\ArrayOf;
use ReallifeKip\ImmutableBase\Attributes\KeepOnNull;
use ReallifeKip\ImmutableBase\Attributes\Lax;
use ReallifeKip\ImmutableBase\Attributes\SkipOnNull;
use ReallifeKip\ImmutableBase\Attributes\Spec;
use ReallifeKip\ImmutableBase\Attributes\ValidateFromSelf;
use ReallifeKip\ImmutableBase\Exceptions\ImmutableBaseException;
use ReallifeKip\ImmutableBase\ImmutableBase;
use ReallifeKip\ImmutableBase\Objects\DataTransferObject;
use ReallifeKip\ImmutableBase\Objects\SingleValueObject;
use ReallifeKip\ImmutableBase\Objects\ValueObject;

require_once './vendor/autoload.php';

ImmutableBase::strict(true);

#[Spec('不符合 Mail 格式')]
readonly class Mail extends SingleValueObject
{
    public string $value;
    public function validate(): bool
    {
        return str_contains($this->value, '@');
    }
}

#[Spec('不符合 Gmail 格式')]
#[ValidateFromSelf]
readonly class Gmail extends Mail
{
    public function validate(): bool
    {
        return str_contains($this->value, 'gmail.com');
    }
}

readonly class YahooMail extends Mail
{
    public function validate(): bool
    {
        return str_contains($this->value, 'yahoo.com');
    }
}

readonly class Hotmail extends Mail
{
    public function validate(): bool
    {
        return str_contains($this->value, 'hotmail.com');
    }
}

readonly class TaiwanGmail extends Gmail
{
    public function validate(): bool
    {
        return str_contains($this->value, '.tw');
    }
}

readonly class Age extends SingleValueObject
{
    public int $value;
}

#[Spec('本系統最低允許註冊年齡為 18 歲')]
readonly class AdultAge extends Age
{
    public function validate(): bool
    {
        return $this->value >= 18;
    }
}

enum Company: string {
    case TechCo = 'Tech Co';
    case BizInc = 'Biz Inc';
    case WebLLC = 'Web LLC';
}

#[Lax]
readonly class WorkExperience extends ValueObject
{
    public Company $company;
    public string $position;
    public int $years;
}

#[Spec('Profile 資料格式錯誤')]
readonly class Profile extends ValueObject
{
    public string $address;
    public string $jobTitle;
    public Company $company;
    #[ArrayOf(WorkExperience::class)]
    public array $workExperiences;
}

enum Sexual: string {
    case M = 'Male';
    case F = 'Female';
}

#[SkipOnNull]
readonly class User extends DataTransferObject
{
    public string $name;
    public Sexual $sexual;
    public AdultAge|null $age;
    #[KeepOnNull]
    public null|Gmail|YahooMail $email;
    /** @description 用戶個人資料 */
    public ?Profile $profile;
    public null $null;
}

#[SkipOnNull]
#[Spec('   Hello world Spec   ')]
readonly class Mini extends ValueObject
{
    public ?string $name;
    public AdultAge|null $age;
    public mixed $mixed;
    public function validate(): bool
    {
        return false;
    }
}

echo '<pre>';
try {
    $workExperience = [
        'company'  => 'Tech Co',
        // 'company'  => 'Systex',
        'position' => 'Intern',
        'years'    => 1,
    ];
    $user = User::fromArray([
        'name'    => 'Kip',
        'email'   => 'example@gmail.com',
        'profile' => [
            'address'         => '123 Main St',
            'jobTitle'        => 'Developer',
            'company'         => Company::BizInc,
            'workExperiences' => [$workExperience, json_encode($workExperience)],
        ],
        'sexual'  => 'M',
        'null'    => null,
    ]);
    $user = $user->with([
        // 'email'                   => Gmail::from('gg@gmail.com'),
        // 'profile.workExperiences' => [
        //     WorkExperience::fromArray([
        //         'company'  => Company::BizInc,
        //         'position' => 'Junior Developer',
        //         'years'    => 2,
        //     ]),
        // ],
        'name'                      => 't',
        'profile.workExperiences.0' => WorkExperience::fromArray([
            'company'  => Company::BizInc,
            'position' => 'Junior Developer',
            'years'    => 2,
        ]),
        // 'sexual' => 'G',
    ]);
    // print_r($user);
    print_r(Mini::fromArray(['mixed' => 'Huh?'])->toArray());
} catch (ImmutableBaseException $e) {
    print_r($e);
}
echo '</pre>';
