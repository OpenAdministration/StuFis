<?php

namespace App\Models\Legacy;

use Database\Factories\ProjectAttachmentFactory;
use Eloquent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * App\Models\ProjectAttachment
 *
 * @property int $id
 * @property int $project_id
 * @property string $name
 * @property string $path
 * @property string $mime_type
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static ProjectAttachmentFactory factory($count = null, $state = [])
 * @method static Builder|ProjectAttachment newModelQuery()
 * @method static Builder|ProjectAttachment newQuery()
 * @method static Builder|ProjectAttachment query()
 * @method static Builder|ProjectAttachment whereCreatedAt($value)
 * @method static Builder|ProjectAttachment whereId($value)
 * @method static Builder|ProjectAttachment whereMimeType($value)
 * @method static Builder|ProjectAttachment whereName($value)
 * @method static Builder|ProjectAttachment wherePath($value)
 * @method static Builder|ProjectAttachment whereProjectId($value)
 * @method static Builder|ProjectAttachment whereUpdatedAt($value)
 *
 * @mixin Eloquent
 */
class ProjectAttachment extends Model
{
    protected $table = 'projekt_attachments';

    public $timestamps = true;

    protected $fillable = ['name', 'path', 'mime_type', 'size'];

    /**
     * Canonical Content-Type per allowed upload extension. Attachments are
     * validated by extension on upload (see edit-project), so both the stored
     * mime_type and the served Content-Type are derived from the extension —
     * NEVER guessed from file content. A file whose bytes are actually HTML/SVG
     * but is named ".png" would otherwise be served as text/html and execute in
     * our origin (stored XSS); none of these types is scriptable-when-rendered
     * (svg is deliberately excluded). finfo also mis-detects ODF/OOXML as
     * "application/zip", so content detection is useless for these formats.
     */
    public const array MIME_TYPES = [
        'pdf' => 'application/pdf',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'odt' => 'application/vnd.oasis.opendocument.text',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
        'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'odp' => 'application/vnd.oasis.opendocument.presentation',
    ];

    /** Canonical MIME type for a filename's extension, or null if not allow-listed. */
    public static function mimeForName(?string $name): ?string
    {
        $extension = strtolower(pathinfo((string) $name, PATHINFO_EXTENSION));

        return self::MIME_TYPES[$extension] ?? null;
    }

    /**
     * The single source of truth for allowed upload extensions. The upload
     * validation rule and the file-input `accept` filter both derive from this,
     * so they can never drift out of sync.
     *
     * @return list<string>
     */
    public static function allowedExtensions(): array
    {
        return array_keys(self::MIME_TYPES);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'projekt_id', 'id');
    }

    protected function humanSize(): Attribute
    {
        return Attribute::make(get: fn () => Attribute::make(
            get: fn () => $this->attributes['size'] / 1024 .'MB',
        ));
    }
}
