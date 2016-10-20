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
{$tab_end}{$fieldstab_start}
 <div class="pageinput pageoverflow">
 {if !empty($fields)}
  {if count($fields)>1}
  <div class="reordermsg pagemessage">
  <p>{$help_can_drag}</p>
  <div class="saveordermsg"><p>{$help_save_order}</p></div>
  </div>
  {/if}
  <table id="fields" class="pagetable{if count($fields)>1} tabledrag{/if}">
   <thead><tr>
  {if isset($title_field_id)}<th>{$title_field_id}</th>{/if}
    <th style="max-width:15em;">{$title_field_name}</th>
  {if isset($title_field_alias)}<th style="width:10em;">{$title_field_alias}</th>{/if}
    <th style="max-width:20em;">{$title_field_type}</th>
    <th style="max-width:25em;">{$title_information}</th>
    <th class="pageicon">{$title_field_required_abbrev}</th>
    <th class="updown" style="width:20px;">&nbsp;</th>
    <th class="updown" style="width:20px;">&nbsp;</th>
    <th class="pageicon"></th>
    <th class="pageicon"></th>
    <th class="pageicon"></th>
   </tr></thead>
   <tbody>
  {foreach from=$fields item=entry}
   {cycle name=fields values='row1,row2' assign=rowclass}
  	 <tr id="pwfp_{$entry->id}" class="{$rowclass}" onmouseover="this.className='{$rowclass}hover';" onmouseout="this.className='{$rowclass}';">
   {if isset($title_field_id)}<td>{$entry->id}</td>{/if}
     <td>{$entry->order}{$entry->name}</td>
   {if isset($title_field_alias)}<td>{$entry->alias}</td>{/if}
     <td>{$entry->type}</td>
     <td>{$entry->field_status}</td>
     <td>{$entry->required}</td>
     <td class="updown">{$entry->up}</td>
     <td class="updown">{$entry->down}</td>
     <td>{$entry->editlink}</td>
     <td>{$entry->copylink}</td>
     <td>{$entry->deletelink}</td>
     </tr>
  {/foreach}
   </tbody>
  </table>
 {else}
 <p>{$nofields}</p>
 {/if}
   <div class="addfast">
    <p class="pagetext">{$title_fieldpick}</p>
    <div>{$input_fieldpick}<br />{$help_fieldpick}</div>
   </div>
   <div class="addslow">{$add_field_link}</div>
  </div>
{$tab_end}{$displaytab_start}
 <div class="pageinput pageoverflow">
 {foreach from=$displays item=entry}
  <p class="pagetext">{$entry->title}:</p>
  <div>{$entry->input}</div>
  {if !empty($entry->help)}<p>{$entry->help}</p>{/if}
 {/foreach}
 </div>
{$tab_end}{$templatetab_start}
 <div class="pageinput">
  <p class="pagetext">{$title_load_template}:</p>
  <p>{$input_load_template}</p>
 </div>
 <br />
 <div class="pageinput pageoverflow">
  <p style="font-weight:bold;">{$title_form_template}:{$icon_info}</p>
  <p>{$input_form_template}</p>
   <div class="showhelp">
  <p style="font-weight:bold;">{$title_tplvars}:</p>
<table class="varshelp">
<tr><th>{$title_variable}</th><th>{$title_description}</th></tr>
{foreach from=$formvars item=entry}
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
{foreach from=$fieldprops item=entry}
{cycle name=fieldprops values='row1,row2' assign=rowclass}
<tr class="{$rowclass}" onmouseover="this.className='{$rowclass}hover';" onmouseout="this.className='{$rowclass}';">
<td>field->{$entry->name}</td><td>{$entry->description}</td></tr>
{/foreach}
</table><br />
  <p>{$help_fieldvars2}</p>
{/if}{*!empty($fieldprops)*}
  </div>
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
 {foreach from=$presubmits item=entry}
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
  <table id="dispositions" class="leftwards{if count($dispositions)>1} tabledrag{/if}">
   <thead><tr>
    <th style="max-width:15em;">{$title_field_name}</th>
    <th style="max-width:20em;">{$title_field_type}</th>
    <th style="max-width:25em;">{$title_information}</th>
    <th class="updown" style="width:20px;">&nbsp;</th>
    <th class="updown" style="width:20px;">&nbsp;</th>
    <th class="pageicon"></th>
    <th class="pageicon"></th>
    <th class="pageicon"></th>
   </tr></thead>
   <tbody>
  {foreach from=$dispositions item=entry}
   {cycle name=fields values='row1,row2' assign=rowclass}
  	 <tr id="pwfp_{$entry->id}" class="{$rowclass}" onmouseover="this.className='{$rowclass}hover';" onmouseout="this.className='{$rowclass}';">
     <td>{$entry->order}{$entry->name}</td>
     <td>{$entry->type}</td>
     <td>{$entry->field_status}</td>
     <td class="updown">{$entry->up}</td>
     <td class="updown">{$entry->down}</td>
     <td>{$entry->editlink}</td>
     <td>{$entry->copylink}</td>
     <td>{$entry->deletelink}</td>
     </tr>
  {/foreach}
   </tbody>
  </table>
 {else}
 <p>{$nodispositions}</p>
 {/if}
 <div class="addfast">
  <p class="pagetext">{$title_fieldpick2}</p>
  <div>{$input_fieldpick2}<br />{$help_fieldpick2}</div>
 </div>
 <div class="addslow">{$add_disposition_link}</div>
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
 {foreach from=$postsubmits item=entry}
  <p class="pagetext">{$entry->title}:</p>
  <div>{$entry->input}</div>
  {if !empty($entry->help)}<p>{$entry->help}</p>{/if}
 {/foreach}
 </div>
{$tab_end}{$externtab_start}
 <div class="pageinput">
 {if !empty($externals)}
  <p class="pagetext">{$title_form_externals}</p>
 {if count($externals)>1}
  <div class="reordermsg pagemessage">
  <p>{$help_can_drag}</p>
  <div class="saveordermsg"><p>{$help_save_order}</p></div>
  </div>
  {/if}
  <table id="externalfield" class="leftwards{if count($externals)>1} tabledrag{/if}">
   <thead><tr>
    <th style="max-width:15em;">{$title_field_name}</th>
    <th style="max-width:20em;">{$title_field_type}</th>
    <th style="max-width:25em;">{$title_information}</th>
    <th class="updown" style="width:20px;">&nbsp;</th>
    <th class="updown" style="width:20px;">&nbsp;</th>
    <th class="pageicon"></th>
    <th class="pageicon"></th>
    <th class="pageicon"></th>
   </tr></thead>
   <tbody>
  {foreach from=$externals item=entry}
   {cycle name=fields values='row1,row2' assign=rowclass}
  	 <tr id="pwfp_{$entry->id}" class="{$rowclass}" onmouseover="this.className='{$rowclass}hover';" onmouseout="this.className='{$rowclass}';">
     <td>{$entry->order}{$entry->name}</td>
     <td>{$entry->type}</td>
     <td>{$entry->field_status}</td>
     <td class="updown">{$entry->up}</td>
     <td class="updown">{$entry->down}</td>
     <td>{$entry->editlink}</td>
     <td>{$entry->copylink}</td>
     <td>{$entry->deletelink}</td>
     </tr>
  {/foreach}
   </tbody>
  </table>
 {else}
 <p>{$noexternals}</p>
 {/if}
 <div class="addfast">
  <p class="pagetext">{$title_fieldpick}</p>
  <div>{$input_fieldpick3}<br />{$help_fieldpick3}</div>
 </div>
 <div class="addslow">{$add_external_link}</div>
 </div>
{$tab_end}
{$tabs_end}
<div class="pageinput" style="margin-top:1em;">{$save} {$cancel} {$apply}</div>
{$form_end}
