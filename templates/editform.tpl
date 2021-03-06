{$backtomod_nav}<br />
{if !empty($message)}{$message}{/if}
{$form_start}{$hidden}
{$tabs_start}
{$maintab_start}
 <div class="pageinput pageoverflow">
  <p class="pagetext">{$title_form_name}:</p>
  <div>{$input_form_name}</div>
  <p class="pagetext">{$title_form_alias}:</p>
  <div>{$input_form_alias}<br />{$help_form_alias}</div>
  <p class="pagetext">{$title_form_status}:</p>
  <p>{if $text_ready}{$text_ready}{else}<strong>{$text_notready}</strong> - {$help_notready}{/if}</p>
 </div>
{$tab_end}{$displaytab_start}
 <div class="pageinput pageoverflow">
 {foreach $displays as $entry}
  <p class="pagetext">{$entry->title}:</p>
  <div>{$entry->input}</div>
  {if !empty($entry->help)}<p>{$entry->help}</p>{/if}
 {/foreach}
 </div>
{$tab_end}{$fieldstab_start}
 <div class="pageinput pageoverflow">
 {if !empty($fields)}
  {if count($fields)>1}
  <div class="reordermsg pagemessage">
  <p>{$help_can_drag}</p>
  <div class="saveordermsg"><p>{$help_save_order}</p></div>
  </div>
  {/if}
  <table id="fields" class="pagetable leftwards{if count($fields)>1} tabledrag{/if}">
   <thead><tr>
  {if isset($text_id)}<th>{$text_id}</th>{/if}
    <th style="max-width:15em;">{$text_name}</th>
  {if isset($text_alias)}<th style="width:10em;">{$text_alias}</th>{/if}
    <th style="max-width:20em;">{$text_type}</th>
    <th style="max-width:25em;">{$text_info}</th>
    <th class="pageicon">{$text_required}</th>
    <th class="updown">{$text_move}</th>
    <th class="pageicon"></th>
    <th class="pageicon"></th>
    <th class="pageicon"></th>
    <th{if count($fields)>1} class="checkbox">{$selectall}{else}>{/if}</th>
   </tr></thead>
   <tbody>
  {foreach $fields as $entry}
   {cycle name=fields values='row1,row2' assign=rowclass}
  	<tr id="pwfp_{$entry->id}" class="{$rowclass}" onmouseover="this.className='{$rowclass}hover';" onmouseout="this.className='{$rowclass}';">
   {if isset($text_id)}<td>{$entry->id}</td>{/if}
     <td>{$entry->order}{$entry->name}</td>
   {if isset($text_alias)}<td>{$entry->alias}</td>{/if}
     <td>{$entry->type}</td>
     <td>{$entry->field_status}</td>
     <td>{$entry->required}</td>
     <td class="updown">{$entry->up}{$entry->down}</td>
     <td>{$entry->edit}</td>
     <td>{$entry->copy}</td>
     <td>{$entry->delete}</td>
     <td class="checkbox">{$entry->select}</td>
    </tr>
  {/foreach}
   </tbody>
  </table>
 {else}
 <p>{$nofields}</p>
 {/if}
   <div class="addfast">
    <p class="pagetext">{$title_fieldpick}</p>
    <div>{$input_fieldpick} {$help_fieldpick}{if $fields}<span style="margin-left:5em;">{$delete}</span>{/if}</div>
   </div>
   <div class="addslow">{$add_field_link}{if $fields}<span style="margin-left:5em;">{$delete}</span>{/if}</div>
  </div>
{$tab_end}{$templatetab_start}
 <div class="pageinput pageoverflow">
  <p style="font-weight:bold;">{$title_form_template}:{$icon_info}</p>
  <p>{$input_form_template}</p>
   <div class="showhelp">
  <p style="font-weight:bold;">{$title_tplvars}:</p>
<table class="varshelp">
<tr><th>{$title_variable}</th><th>{$title_description}</th></tr>
{foreach $formvars as $entry}
{cycle name=globals values='row1,row2' assign=rowclass}
 <tr class="{$rowclass}" onmouseover="this.className='{$rowclass}hover';" onmouseout="this.className='{$rowclass}';">
 <td>{ldelim}${$entry->name}{rdelim}</td><td>{$entry->description}</td></tr>
{/foreach}
</table><br />
  <p>{$help_tplvars}</p>
{if !empty($fieldprops)}
  <p>{$help_fieldvars1}</p>
<table class="varshelp">
<tr><th>{$title_property}</th><th>{$title_description}</th></tr>
{foreach $fieldprops as $entry}
{cycle name=fieldprops values='row1,row2' assign=rowclass}
<tr class="{$rowclass}" onmouseover="this.className='{$rowclass}hover';" onmouseout="this.className='{$rowclass}';">
<td>field->{$entry->name}</td><td>{$entry->description}</td></tr>
{/foreach}
</table><br />
  <p>{$help_fieldvars2}</p>
{/if}{*!empty($fieldprops)*}
  </div>
 </div>
 <div class="pageinput">
  <p class="pagetext">{$title_load_template}:</p>
  <p>{$input_load_template}</p>
 </div>
{$tab_end}{$udttab_start}
 <div class="pageinput pageoverflow">
  <p class="pagetext">{$title_form_predisplay_udt}:</p>
  <p>{$input_form_predisplay_udt}</p>
  <p class="pagetext">{$title_form_predisplay_each_udt}:</p>
  <p>{$input_form_predisplay_each_udt}</p>
  <p class="pagetext">{$title_form_validate_udt}:</p>
  <p>{$input_form_validate_udt}</p>
  <br />
  <p>{$help_udt}</p>
 </div>
{$tab_end}{$submittab_start}
 <div class="pageinput pageoverflow">
 {foreach $presubmits as $entry}
  <p class="pagetext">{$entry->title}:</p>
  <div>{$entry->input}</div>
  {if !empty($entry->help)}<p>{$entry->help}</p>{/if}
 {/foreach}
 {if !empty($dispositions)}
  <p class="pagetext">{$title_form_dispositions}</p>
  {if count($dispositions)>1}
  <div class="reordermsg pagemessage">
  <p>{$help_can_drag}</p>
  <div class="saveordermsg"><p>{$help_save_order}</p></div>
  </div>
  {/if}
  <table id="dispositions" class="pagetable leftwards{if count($dispositions)>1} tabledrag{/if}">
   <thead><tr>
    <th style="max-width:15em;">{$text_name}</th>
    <th style="max-width:20em;">{$text_type}</th>
    <th style="max-width:25em;">{$text_info}</th>
    <th class="updown">{$text_move}</th>
    <th class="pageicon"></th>
    <th class="pageicon"></th>
    <th class="pageicon"></th>
    <th{if count($dispositions)>1} class="checkbox">{$selectall}{else}>{/if}</th>
   </tr></thead>
   <tbody>
  {foreach $dispositions as $entry}
   {cycle name=fields values='row1,row2' assign=rowclass}
  	<tr id="pwfp_{$entry->id}" class="{$rowclass}" onmouseover="this.className='{$rowclass}hover';" onmouseout="this.className='{$rowclass}';">
     <td>{$entry->order}{$entry->name}</td>
     <td>{$entry->type}</td>
     <td>{$entry->field_status}</td>
     <td class="updown">{$entry->up}{$entry->down}</td>
     <td>{$entry->edit}</td>
     <td>{$entry->copy}</td>
     <td>{$entry->delete}</td>
     <td class="checkbox">{$entry->select}</td>
    </tr>
  {/foreach}
   </tbody>
  </table>
 {else}
 <p>{$nodispositions}</p>
 {/if}
 <div class="addfast">
  <p class="pagetext">{$title_fieldpick2}</p>
  <div>{$input_fieldpick2} {$help_fieldpick2}{if $dispositions}<span style="margin-left:5em;">{$delete}</span>{/if}</div>
 </div>
 <div class="addslow">{$add_disposition_link}{if $dispositions}<span style="margin-left:5em;">{$delete}</span>{/if}</div>
  <br />
  <p class="pagetext">{$title_submit_action}:</p>
  <p>{$input_submit_action}</p>
  <div id="pageobjects">
  <p class="pagetext">{$title_redirect_page}:</p>
  <p>{$input_redirect_page}</p>
  </div>
  <div id="tplobjects">
  <p class="pagetext">{$title_submit_template}:{$icon_info}</p>
  <div class="pageoverflow">{$input_submit_template}</div>
  <p>{$help_submit_template}<br /><br />{$sample_submit_template}</p>
  <div class="showhelp"><br />{$help_subtplvars}</div>
  </div>
 {foreach $postsubmits as $entry}
  <p class="pagetext">{$entry->title}:</p>
  <div>{$entry->input}</div>
  {if !empty($entry->help)}<p>{$entry->help}</p>{/if}
 {/foreach}
 </div>
{$tab_end}
{$tabs_end}
<div class="pageinput" style="margin-top:1em;">{$save} {$cancel} {$apply}</div>
{$form_end}
