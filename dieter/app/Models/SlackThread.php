<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['channel', 'thread_ts', 'conversation_id'])]
class SlackThread extends Model {}
