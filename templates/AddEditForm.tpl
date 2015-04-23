{$backtomod_nav}<br />
{if isset($message)}{$message}<br />{/if}
{$formstart}
{$formid}{$fb_hidden}
{$tab_start}

{$maintab_start}
 <div class="pageoverflow">
  <p class="pagetext">{$title_form_name}:</p>
  <p class="pageinput">{$input_form_name}</p>
{if $adding == 0}
  <p class="pagetext">{$title_form_status}:</p>
  <p class="pageinput">{if $hasdisposition == 1}{$text_ready}{else}{$link_notready}{/if}</p>
{/if}
  <p class="pagetext">{$title_form_alias}:</p>
  <p class="pageinput">{$input_form_alias}</p>
  <p class="pagetext">{$title_inline_form}:</p>
  <p class="pageinput">{$input_inline_form}</p>
 </div>
{$tab_end}{$fieldstab_start}
{if $adding==0}
 <div class="pageoverflow">
  <p class="pagetext">{$title_form_fields}</p>
  <table class="pwf_table pagetable tabledrag">
   <thead><tr>
  {if isset($title_field_id)}<th>{$title_field_id}</th>{/if}
    <th style="width:15em;">{$title_field_name}</th>
  {if isset($title_field_alias)}<th style="width:10em;">{$title_field_alias}</th>{/if}
    <th style="width:20em;">{$title_field_type}</th>
    <th style="width:25em;">{$title_information}</th>
    <th class="pageicon">{$title_field_required_abbrev}</th>
    <th class="updown" style="width:20px;">&nbsp;</th>
    <th class="updown" style="width:20px;">&nbsp;</th>
    <th class="pageicon">&nbsp;</th>
    <th class="pageicon">&nbsp;</th>
    <th class="pageicon">&nbsp;</th>
   </tr></thead>
   <tbody>
  {foreach from=$fields item=entry}
   {cycle name=fields values='odd,even' assign=rowclass}
  	 <tr id="pwfp_{$entry->id}" class="{$rowclass}" onmouseover="this.className='{$rowclass}hover';" onmouseout="this.className='{$rowclass}';">
   {if isset($title_field_id)}<td>{$entry->id}</td>{/if}
     <td>{$entry->name}</td>
   {if isset($title_field_alias)}<td>{$entry->alias}</td>{/if}
     <td>{$entry->type}</td>
     <td>{$entry->field_status}</td>
     <td>{$entry->disposition}</td>
     <td class="updown">{$entry->up}</td>
     <td class="updown">{$entry->down}</td>
     <td>{$entry->editlink}</td>
     <td>{$entry->copylink}</td>
     <td>{$entry->deletelink}</td>
     </tr>
  {/foreach}
   </tbody>
  </table>
  <div class="reordermsg pagemessage" style="margin-left:10%;display:none">
  <p>{$title_can_drag}</p>
  <div class="saveordermsg" style="display:none"><p>{$title_must_save_order}</p></div>
  </div>
 {if $fastadd==1}
  <div class="pageoverflow">
   <p class="pagetext">{$title_fastadd}</p>
   <div class="pageinput">
  {$input_fastadd}
   </div>
  </div>
 {/if}
   <br />
  <div class="pageinput">{$add_field_link}</div>
    </div>
{/if}
{$tab_end}{$designtab_start}
 <div class="pageoverflow">
  <p class="pagetext">{$title_form_css_class}:</p>
  <p class="pageinput">{$input_form_css_class}</p>
  <p class="pagetext">{$title_form_required_symbol}:</p>
  <p class="pageinput">{$input_form_required_symbol}</p>
  <p class="pagetext">{$title_list_delimiter}:</p>
  <p class="pageinput">{$input_list_delimiter}</p>
  <p class="pagetext">{$title_form_unspecified}:</p>
  <p class="pageinput">{$input_form_unspecified}</p>
{if $captcha_installed}
  <p class="pagetext">{$title_use_captcha}:</p>
  <p class="pageinput">{$input_use_captcha}</p>
  <p class="pagetext">{$title_title_user_captcha}:</p>
  <p class="pageinput">{$input_title_user_captcha}</p>
  <p class="pagetext">{$title_user_captcha_error}:</p>
  <p class="pageinput">{$input_title_user_captcha_error}</p>
{else}
  <p class="pageinput">{$title_install_captcha}</p>
{/if}
  <p class="pagetext">{$title_form_submit_button}:</p>
  <p class="pageinput">{$input_form_submit_button}</p>
  <p class="pagetext">{$title_form_next_button}:</p>
  <p class="pageinput">{$input_form_next_button}</p>
  <p class="pagetext">{$title_form_prev_button}:</p>
  <p class="pageinput">{$input_form_prev_button}</p>
 </div>
{$tab_end}{$templatetab_start}
 <div class="pageoverflow">
  <p class="pagetext">{$title_load_template}:</p>
  <p class="pageinput">{$input_load_template}</p>
 </div>
 <br />
 <div class="pageinput pageoverflow">
  <p style="font-weight:bold;">{$title_form_template}:</p>
  <p>{$input_form_template}</p>
  <p style="font-weight:bold;">{$title_form_vars}:</p>
<table class="pwf_legend">
<tr><th>{$variable}</th><th>{$description}</th></tr>
{foreach from=$globalfields item=entry}
{cycle name=globals values='odd,even' assign=rowclass}
 <tr class="{$rowclass}" onmouseover="this.className='{$rowclass}hover';" onmouseout="this.className='{$rowclass}';">
 <td>{ldelim}${$entry->name}{rdelim}</td><td>{$entry->description}</td></tr>
{/foreach}
</table><br />
  <p>{$globals_help1}</p>
  <p>{$attrs_help1}</p>
<table class="pwf_legend">
<tr><th>{$attribute}</th><th>{$description}</th></tr>
{foreach from=$attrs item=entry}
{cycle name=attrs values='odd,even' assign=rowclass}
<tr class="{$rowclass}" onmouseover="this.className='{$rowclass}hover';" onmouseout="this.className='{$rowclass}';">
<td>field->{$entry->name}</td><td>{$entry->description}</td></tr>
{/foreach}
</table><br />
  <p>{$attrs_help2}</p>
 </div>
{$tab_end}{$submittab_start}
<p>{$icon_info}&nbsp;{$title_submit_help}</p>
 <div class="pageoverflow">
  <p class="pagetext">{$title_submit_action}:</p>
  <p class="pageinput">{$input_submit_action}</p>
  <p class="pagetext">{$title_redirect_page}:</p>
  <p class="pageinput">{$input_redirect_page}</p>
  <p class="pagetext">{$title_submit_button_safety}:</p>
  <p class="pageinput">{$input_submit_button_safety}</p>
  <p class="pagetext">{$title_submit_javascript}:</p>
  <p class="pageinput">{$input_submit_javascript}</p>
 </div>
{$tab_end}{$udttab_start}
 <div class="pageoverflow">
{*<p class="pagetext">{$title_see_also_udt}</p>*}
  <p class="pagetext">{$title_form_predisplay_udt}:</p>
  <p class="pageinput">{$input_form_predisplay_udt}</p>
  <p class="pagetext">{$title_form_predisplay_each_udt}:</p>
  <p class="pageinput">{$input_form_predisplay_each_udt}</p>
  <p class="pagetext">{$title_form_validate_udt}:</p>
  <p class="pageinput">{$input_form_validate_udt}</p>
 </div>
{$tab_end}{$submittemplatetab_start}
 <p>{$icon_info}&nbsp;{$title_submit_template_help}</p>
 <div class="pageinput pageoverflow">
  <p style="font-weight:bold;">{$title_submit_template}:</p>
{$input_submit_template}<br />
{if isset($buttons) && !empty($buttons)}
  <br />
{foreach from=$buttons item=one name=buttons}
{$one}{if !$smarty.foreach.buttons.last}&nbsp;{/if}
{/foreach}
  <br />
{/if}
 <br />
{$help_vars}
 </div>
{$tab_end}
{$tabs_end}
 <div class="pageoverflow">
  <br />
  <p class="pageinput">{$save} {$cancel} {$apply}</p>
 </div>
{$form_end}
<script type="text/javascript" src="{$incpath}jquery.tablednd.min.js"></script>
<script type="text/javascript" src="{$incpath}fb_jquery_functions.js"></script>
<script type="text/javascript" src="{$incpath}fb_jquery.js"></script>
{if !empty($jsfuncs)}
<script type="text/javascript">
/* <![CDATA[ */
{foreach from=$jsfuncs item=func}{$func}{/foreach}
/* ]]> */
</script>
{/if}
