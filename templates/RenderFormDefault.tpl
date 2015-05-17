{* DEFAULT FORM LAYOUT / pure CSS *}
<script type="text/javascript">
//<![CDATA[{literal}
function help_toggle(htid) {
 var help_container=document.getElementById(htid);
 if(help_container) {
  if(help_container.style.display == 'none') {
	help_container.style.display = 'inline';
  } else {
    help_container.style.display = 'none';
  }
 }
}
{/literal}//]]>
</script>
{if $form_done}
	{* This section is for displaying submission-errors *}
	{if !empty($submission_error)}
		<div class="error_message">{$submission_error}</div>
		{if $show_submission_errors}
			<div class="error">
			<ul>
			{foreach from=$submission_error_list item=one}
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
		{foreach from=$form_validation_errors item=one}
			<li>{$one}</li>
		{/foreach}
		</ul>
		</div>
	{/if}
	{if !empty($captcha_error)}
		<div class="error_message">{$captcha_error}</div>
	{/if}
	{* and now the form itself *}
	{$form_start}
	<div>{$hidden}</div>
	<div{if $css_class} class="{$css_class}"{/if}>
	{if $total_pages gt 1}<span>{$title_page_x_of_y}</span>{/if}
	{foreach from=$fields item=one}
		{strip}
		{if $one->display}
			{if $one->needs_div}
				<div
				{if $one->required || $one->css_class || !$one->valid} class="
					{if $one->required}required {/if}
					{if $one->css_class}{$one->css_class} {/if}
					{if !$one->valid}pwf_invalid{/if}
					"
				{/if}
				>
			{/if}
			{if !$one->hide_name}
				<label{if $one->multiple_parts != 1} for="{$one->input_id}"{/if}>{$one->name}
				{if $one->required_symbol}{$one->required_symbol}{/if}
				</label>
			{/if}
			{if $one->multiple_parts}
				{section name=numloop loop=$one->input}
					{if $one->label_parts}
						<div>{$one->input[numloop]->input}&nbsp;{$one->input[numloop]->name}</div>
					{else}
						{$one->input[numloop]->input}
					{/if}
					{if !empty($one->input[numloop]->op)}{$one->input[numloop]->op}{/if}
				{/section}
			{else}
				{if $one->smarty_eval}{eval var=$one->input}{else}{$one->input}{/if}
			{/if}
			{if $one->helptext != ''}&nbsp;<a href="javascript:help_toggle('{$one->field_helptext_id}')">
				<img src="modules/PowerForms/images/info-small.gif" alt="Help" title="help" /></a>
				<span id="{$one->field_helptext_id}" class="pwf_helptext">{$one->helptext}</span>{/if}
			{if !$one->valid} &lt;--- {$one->error}{/if}
			{if $one->needs_div}
				</div>
			{/if}
		{/if}
		{/strip}
	{/foreach}
	{if !empty($has_captcha)}
		<div class="captcha">{$graphic_captcha}{$title_captcha}<br />{$input_captcha}<br /></div>
	{/if}
	<div class="submit">{$prev} {$submit}</div>
	</div>
	{$form_end}
{/if}
