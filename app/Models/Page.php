<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use JacobJoergensen\LaravelPaper\Attributes\ContentPath;
use JacobJoergensen\LaravelPaper\Attributes\Driver;
use JacobJoergensen\LaravelPaper\Paper;

/**
 * Static page stored as a Markdown file in content/pages, keyed by its filename.
 *
 * @property string $slug
 * @property string $title
 * @property string|null $description
 * @property string $content
 */
#[Driver('markdown')]
#[ContentPath('content/pages')]
class Page extends Model
{
    use Paper;
}
