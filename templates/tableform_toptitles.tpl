{* TABLE FORM LAYOUT / Field titles on top *}
{* next line sets number of columns for things like checkbox groups *}
{assign var="cols" value="3"}
{if $form_done}
	{* This section is for displaying submission-errors *}
	{if !empty($submission_error)}
		<div class="error_message">{$submission_error}</div>
		{if $show_submission_errors}
			<table class="error_list">
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
		<div class="error_list">
		<ul>
		{foreach from=$form_validation_errors item=one}
			<li>{$one}</li>
		{/foreach}
		</ul>
		</div>
	{/if}
	{* and now the form itself *}
	<table{if $css_class} class="{$css_class}"{/if}>
	{if $total_pages gt 1}<tr><td colspan="2">{$title_page_x_of_y}</td></tr>{/if}
	{foreach from=$fields item=one}
		{strip}
		{if $one->display && $one->type != 'FieldsetStart' && $one->type != 'FieldsetEnd'}
		<tr>
			<td style="vertical-align:top;"
			{if $one->required || !empty($one->css_class)} class="{if $one->required}required{/if}{if !empty($one->css_class)} {$one->css_class}{/if}"{/if}>
			{if !$one->hide_name}{$one->name}
			{if $one->required && $one->required_symbol}{$one->required_symbol}{/if}
			{/if}
			</td></tr><tr><td style="text-align:left;vertical-align:top;"
			{if $one->required || !empty($one->css_class)} class="{if $one->required}required{/if}{if !empty($one->css_class)} {$one->css_class}{/if}"{/if}>
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
			{if $one->helptext}&nbsp;<a href="javascript:help_toggle('{$one->helptext_id}')">{$help_icon}</a>
			<span id="{$one->helptext_id}" class="help_display">{$one->helptext}</span>{/if}
			</td></tr>
		{/if}
		{/strip}
	{/foreach}
	</table>
  <div class="submit_actions">{$prev} {$submit} {$cancel}</div>
{/if}
