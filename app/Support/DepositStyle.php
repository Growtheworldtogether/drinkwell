<?php

namespace App\Support;

use App\Models\MachineOwnership;
use App\Models\PairingRequest;
use App\Models\User;
use App\Services\BrandService;
use App\Services\SettingsRepository;
use Illuminate\Support\HtmlString;

/**
 * How a paired member is told to pay, chosen in Admin, Settings, Deposit details.
 *
 * Style 1 (bank): the member's page shows the co-partner's bank details, and the pairing email carries them.
 * Style 2 (contact): no bank details anywhere and no pairing email. The member goes through three screens: the
 * co-partner's name, location and email; the admin's payment instruction (ticked as read); and a ready-made email
 * to send the co-partner.
 */
final class DepositStyle
{
    public const KEY = 'deposit_style';

    public const BANK = 'bank';

    public const CONTACT = 'contact';

    public const INSTRUCTION_KEY = 'payment_instruction';

    public const SUBJECT_KEY = 'partner_email_subject';

    public const BODY_KEY = 'partner_email_body';

    /** Fill-in words the admin can use, with what each becomes. */
    public const WORDS = [
        'member_name' => "The member's full name",
        'member_first_name' => "The member's first name",
        'partner_name' => "The co-partner's name",
        'partner_location' => "The co-partner's location",
        'partner_email' => "The co-partner's email",
        'amount' => 'The deposit amount, for example $5,000.00',
        'option' => 'The option chosen, for example Commercial Partner Unit',
        'machine' => 'The machine serial number',
        'machine_location' => 'Where the machine stands',
        'reference' => 'The payment reference',
        'site_name' => 'The name of this site',
    ];

    public const DEFAULT_INSTRUCTION = "Please read this carefully before you send anything.\n\n- Contact your co-partner, {partner_name}, by email first. The next screen has a message ready for you.\n- Your co-partner will reply with how to send your {amount}.\n- Only send your deposit to the details your co-partner gives you.\n- Keep your receipt. You upload it on your progress page.\n\nIf anything is unclear, write to us before you send money.";

    public const DEFAULT_SUBJECT = 'Our co-partnership on machine {machine}';

    public const DEFAULT_BODY = "Hello {partner_name},\n\nMy name is {member_name}. {site_name} has paired us as co-partners on machine {machine} at {machine_location}.\n\nI would like to send my share of {amount}. Please let me know how you would like to receive it.\n\nMy payment reference is {reference}.\n\nKind regards,\n{member_name}";

    public static function current(?SettingsRepository $settings = null): string
    {
        $value = ($settings ?? app(SettingsRepository::class))->string(self::KEY, self::BANK);

        return $value === self::CONTACT ? self::CONTACT : self::BANK;
    }

    public static function isContact(?SettingsRepository $settings = null): bool
    {
        return self::current($settings) === self::CONTACT;
    }

    /** @return array{instruction: string, subject: string, body: string} The admin's texts, or the starting texts when empty. */
    public static function texts(?SettingsRepository $settings = null): array
    {
        $s = $settings ?? app(SettingsRepository::class);
        $pick = fn (string $key, string $default) => trim($s->string($key)) !== '' ? $s->string($key) : $default;

        return [
            'instruction' => $pick(self::INSTRUCTION_KEY, self::DEFAULT_INSTRUCTION),
            'subject' => $pick(self::SUBJECT_KEY, self::DEFAULT_SUBJECT),
            'body' => $pick(self::BODY_KEY, self::DEFAULT_BODY),
        ];
    }

    /** @return array<string, string> The fill-in words for one paired member. */
    public static function wordsFor(User $member, PairingRequest $req, MachineOwnership $mine): array
    {
        $machine = $mine->machine;

        return self::words([
            'member_name' => $member->name,
            'partner_name' => (string) $req->partner_name,
            'partner_location' => (string) $req->partner_city,
            'partner_email' => (string) $req->partner_email,
            'amount' => Money::format($mine->capital_cents),
            'option' => $machine->tier->label(),
            'machine' => $machine->serial_no,
            'machine_location' => $machine->facility_name.', '.$machine->city,
            'reference' => $req->reference ?: \App\Services\PairingService::reference($mine),
        ]);
    }

    /** @param  array<string, string>  $w  @return array<string, string> */
    public static function words(array $w): array
    {
        $w['member_first_name'] = $w['member_first_name'] ?? (string) strtok($w['member_name'] ?? '', ' ');
        $w['site_name'] = app(BrandService::class)->name();

        return $w;
    }

    /** Swaps each {word} for its value. Unknown words are left as typed, so a typo shows up in the preview. */
    public static function fill(string $text, array $words): string
    {
        return preg_replace_callback('/\{([a-z_]+)\}/', fn ($m) => array_key_exists($m[1], $words) ? $words[$m[1]] : $m[0], $text) ?? $text;
    }

    /**
     * Plain text to safe HTML: an empty line starts a new paragraph, lines starting with "- " become a bulleted
     * list, and other line breaks are kept. Everything is escaped first, so no HTML typed by the admin runs.
     */
    public static function format(string $text): HtmlString
    {
        $html = [];
        foreach (preg_split('/\R[ \t]*\R/', trim($text)) ?: [] as $block) {
            // Group the lines of one paragraph into runs of plain lines and runs of "- " bullet lines.
            $runs = [];
            foreach (preg_split('/\R/', $block) ?: [] as $line) {
                if (trim($line) === '') {
                    continue;
                }
                $bullet = (bool) preg_match('/^\s*[-*\x{2022}]\s+(.*)$/u', $line, $m);
                $item = $bullet ? trim($m[1]) : trim($line);
                if ($runs !== [] && end($runs)[0] === $bullet) {
                    $runs[array_key_last($runs)][1][] = $item;
                } else {
                    $runs[] = [$bullet, [$item]];
                }
            }
            foreach ($runs as [$bullet, $items]) {
                $html[] = $bullet
                    ? '<ul>'.implode('', array_map(fn ($i) => '<li>'.e($i).'</li>', $items)).'</ul>'
                    : '<p>'.implode('<br>', array_map('e', $items)).'</p>';
            }
        }

        return new HtmlString(implode("\n", $html));
    }
}
