<?php

namespace Kompo\Tasks\Components\General;

use Kompo\CKEditor;
use Illuminate\Support\Str;

class CKEditorExtended extends CKEditor
{
	public $vueComponent = 'CKEditorExtended';

	public function bootMentions()
	{
		$this->config([
			'date-component' => _DateTime()->class('datetime-mention mb-0'),
		]);

		$this->addMention('@', $this->users()->get(), 0, 'icon-profile', 'name', 'user', __('tasks.user'));

        return $this;
	}

	protected function users()
	{
		return currentTeam()->users();
	}

	public static function parseText($text, $modelLabel)
	{
		$dataMentions = collect(explode('data-mention="', $text))->map(function($text){
			return Str::before($text, '"');
		});

		$dataMentions->shift();

		return $dataMentions->map(function($mention) use($modelLabel, $text){

			$mention = explode('|', $mention);

			if(count($mention) < 2 || count($mention) > 3){
				\Log::info('ERROR while processing '.$modelLabel.' with: '.$text);
				return;
			}

			return $mention;

		})->filter();
	}
}
