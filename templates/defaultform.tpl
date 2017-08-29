{if $form_done}
	{* This section is for displaying submission-errors *}
	{if !empty($submission_error)}
		<div class="error_message">{$submission_error}</div>
		{if $show_submission_errors}
			<div class="error_list">
			<ul>
			{foreach $submission_error_list as $one}
				<li>{$one}</li>
			{/foreach}
			</ul>
			</div>
		{/if}
	{/if}
{else}
	{* This section is for displaying the form *}
	{* we start with validation errors *}
	{if !empty($form_has_validation_errors)}
		<div class="error_message">
		<ul>
		{foreach $form_validation_errors as $one}
			<li>{$one}</li>
		{/foreach}
		</ul>
		</div>
	{/if}
	{* and now the form itself *}
	<div{if $css_class} class="{$css_class}"{/if}>
	{if $total_pages > 1}<span>{$title_page_x_of_y}</span>{/if}
	{foreach $fields as $one}
		{strip}
		{if $one->display}
			{if $one->needs_div}<div
				{if $one->required || !empty($one->css_class)} class="{if $one->required}required{/if}{if !empty($one->css_class)} {$one->css_class}{/if}"{/if}
			>{/if}
			{if !$one->hide_name}
				<label{if !$one->multiple_parts} for="{$one->input_id}"{/if}>{$one->name}
				{if $one->required && $one->required_symbol}{$one->required_symbol}{/if}
				</label>
			{/if}
			{if $one->multiple_parts}
				{foreach $one->input as $part}
					{if $one->label_parts}
						<div>{$part->input}&nbsp;{$part->name}</div>
					{else}
						{$part->input}
					{/if}
					{if !empty($part->op)}{$part->op}{/if}
				{/foreach}
			{else}
				{if $one->smarty_eval}{eval var=$one->input}{else}{$one->input}{/if}
			{/if}
			{if $one->helptext}&nbsp;<a href="javascript:help_toggle('{$one->helptext_id}')">{$help_icon}</a>
			<span id="{$one->helptext_id}" class="help_display">{$one->helptext}</span>{/if}
			{if !$one->valid} &lt;--- {$one->error}{/if}
			{if $one->needs_div}</div>{/if}
		{/if}
		{/strip}
	{/foreach}
	<div class="submit_actions">{$prev} {$submit} {$cancel}</div>
	</div>
{/if}
