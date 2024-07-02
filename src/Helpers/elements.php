<?php

use Kompo\Tasks\Components\General\CKEditorExtended;

/* COMPONENTS */
function _CKEditorExtended($label = '')
{
    return CKEditorExtended::form($label)->bootMentions();
}

/* STYLED ELEMENTS */
function _CardHeader($title, $buttons = [])
{
    return _FlexBetween(
        _Html($title)->class('font-bold text-lg text-level1'),
        _FlexEnd(
            collect($buttons)->filter()->map(function($button){
                return $button->class('text-gray-600');
            })
        )
    )->class('bg-white px-4 py-4 rounded-t-2xl');
}

function _CeLinkGroup($label = null)
{
    return _LinkGroup($label)->selectedClass('border-b-2 border-level3 text-level1 font-semibold rounded-lg', 'border-b-2 border-transparent text-level1 rounded-lg');
}

function _IconFilter($name, $icon, $label, $operator = null)
{
    return _CeLinkGroup()
        ->name($name)
        ->options([1 => _Html()->icon(_Sax($icon,20))->balloon($label, 'up')])
        ->class('mb-0')
        ->filter($operator);
}

function _FilterToggler($id, $withLabel = true)
{
	return _Button($withLabel ? ('<span class="hidden sm:inline">'.__('tasks-filter').'</span>') : '')
        ->icon(
            _Sax('filter',22)->class('text-xl')
        )
        ->toggleId($id)
        ->class('!bg-level4 !text-level1 ');
}