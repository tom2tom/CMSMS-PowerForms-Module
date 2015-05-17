{* TABLE FORM LAYOUT / Field titles on Top *}
{* next line sets number of columns for things like checkbox groups *}
{assign var="cols" value="3"}
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
			<table class="error">
			{foreach from=$submission_error_list item=one}
				<tr><td>{$one}</td></tr>
			{/foreach}
			</table>
		{/if}
	{/if}
{else}
	{* This section is for displaying the form *}
	{* we start with validation errors *}
	{if $form_has_validation_errors}
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

	<table{if $css_class != ''} class="{$css_class}"{/if}>
	{if $total_pages gt 1}<tr><td colspan="2">{$title_page_x_of_y}</td></tr>{/if}
	{foreach from=$fields item=one}
		{strip}
		{if $one->display &&
			$one->type != '-Fieldset Start' &&
			$one->type != '-Fieldset End' }
		<tr>
			<td style="vertical-align:top;"
			{if $one->required || $one->css_class} class=" 
				{if $one->required}required {/if}
				{if $one->css_class}{$one->css_class}{/if}
				"
			{/if}
			>
			{if !$one->hide_name}{$one->name}
			{if $one->required_symbol}{$one->required_symbol}{/if}
			{/if}
			</td></tr><tr><td style="text-align:left;vertical-align:top;"{if $one->css_class} class="{$one->css_class}"{/if}>
			{if $one->multiple_parts}
			<table>
				<tr>
				{section name=numloop loop=$one->input}
					<td>{$one->input[numloop]->input}&nbsp;{$one->input[numloop]->name}	{if !empty($one->input[numloop]->op)}&nbsp;{$one->input[numloop]->op}{/if}</td>
					{if not ($smarty.section.numloop.rownum mod $cols)}
						{if not $smarty.section.numloop.last}
				</tr><tr>
						{/if}
					{/if}
					{if $smarty.section.numloop.last}
						{math equation = "n - a % n" n=$cols a=$one->input|@count assign="cells"}
						{if $cells ne $cols}
							{section name=pad loop=$cells}
								<td>&nbsp;</td>
							{/section}
						{/if}
				</tr>
					{/if}
				{/section}
			</table>
			{else}
				{if $one->smarty_eval}{eval var=$one->input}{else}{$one->input}{/if}
			{/if}
			{if !$one->valid} &lt;--- {$one->error}{/if}
			{if $one->helptext != ''}&nbsp;<a href="javascript:help_toggle('{$one->field_helptext_id}')">
				<img src="modules/PowerForms/images/info-small.gif" alt="Help" title="help" /></a>
				<span id="{$one->field_helptext_id}" class="pwf_helptext">{$one->helptext}</span>{/if}
			</td></tr>
		{/if}
		{/strip}
	{/foreach}
	{if !empty($has_captcha)}
	<tr><td>{$graphic_captcha}</td></tr><tr><td>{$input_captcha}<br />{$title_captcha}</td></tr>
	{/if}
	<tr><td>{$prev}</td></tr><tr><td>{$submit}</td></tr>
	</table>
	{$form_end}
{/if}
