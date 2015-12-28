{$backtomod_nav}<br />
{if !empty($message)}{$message}{/if}
{$form_start}{$hidden}
{$tabs_start}
{$maintab_start}
 <div class="pageoverflow">
  <p class="pagetext">{$title_form_name}:</p>
  <div class="pageinput">{$input_form_name}</div>
  <p class="pagetext">{$title_form_alias}:</p>
  <div class="pageinput">{$input_form_alias}<br />{$help_form_alias}</div>
  <p class="pagetext">{$title_inline_form}:</p>
  <p class="pageinput">{$input_inline_form}</p>
  <p class="pagetext">{$title_form_status}:</p>
  <p class="pageinput">{if $text_ready}{$text_ready}{else}<strong>{$text_notready}</strong> - {$help_notready}{/if}</p>
 </div>
{$tab_end}{$fieldstab_start}
 <div class="pageinput pageoverflow">
 {if !empty($fields)}
  <p class="pagetext">{$title_form_fields}</p>
  <div class="reordermsg pagemessage" style="display:none">
  <p>{$help_can_drag}</p>
  <div id="saveordermsg" style="display:none"><p>{$help_save_order}</p></div>
  </div>
  <table id="fields" class="pagetable leftwards tabledrag">
   <thead><tr>
  {if isset($title_field_id)}<th>{$title_field_id}</th>{/if}
    <th style="width:15em;">{$title_field_name}</th>
  {if isset($title_field_alias)}<th style="width:10em;">{$title_field_alias}</th>{/if}
    <th style="width:20em;">{$title_field_type}</th>
    <th style="width:25em;">{$title_information}</th>
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
	  <p class="pagetext">{$title_fastadd}</p>
	  <div>{$input_fastadd}<br />{$help_fastadd}</div>
	 </div>
  	 <div class="addslow">{$add_field_link}</div>
 </div>
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
  <p style="font-weight:bold;">{$title_form_template}:{$icon_info}</p>
  <p>{$input_form_template}</p>
   <div class="showhelp">
  <p style="font-weight:bold;">{$title_form_vars}:</p>
<table class="varshelp">
<tr><th>{$title_variable}</th><th>{$title_description}</th></tr>
{foreach from=$formvars item=entry}
{cycle name=globals values='row1,row2' assign=rowclass}
 <tr class="{$rowclass}" onmouseover="this.className='{$rowclass}hover';" onmouseout="this.className='{$rowclass}';">
 <td>{ldelim}${$entry->name}{rdelim}</td><td>{$entry->description}</td></tr>
{/foreach}
</table><br />
  <p>{$help_formvars}</p>
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
 <div class="pageoverflow">
{*<p class="pagetext">{$help_see_udt}</p>*}
  <p class="pagetext">{$title_form_predisplay_udt}:</p>
  <p class="pageinput">{$input_form_predisplay_udt}</p>
  <p class="pagetext">{$title_form_predisplay_each_udt}:</p>
  <p class="pageinput">{$input_form_predisplay_each_udt}</p>
  <p class="pagetext">{$title_form_validate_udt}:</p>
  <p class="pageinput">{$input_form_validate_udt}</p>
 </div>
{$tab_end}{$submittab_start}
 <div class="pageinput pageoverflow">
  <p class="pagetext">{$title_submit_button_safety}:</p>
  <p>{$input_submit_button_safety}</p>
  <p class="pagetext">{$title_submit_javascript}:</p>
  <p>{$input_submit_javascript}</p>
 {if !empty($dispositions)}
  <p class="pagetext">{$title_form_dispositions}</p>
  <div class="reordermsg pagemessage" style="display:none">
  <p>{$help_can_drag}</p>
  <div id="saveordermsg" style="display:none"><p>{$help_save_order}</p></div>
  </div>
  <table id="dispositions" class="pageinput tabledrag">
   <thead><tr>
    <th style="width:15em;">{$title_field_name}</th>
    <th style="width:20em;">{$title_field_type}</th>
    <th style="width:25em;">{$title_information}</th>
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
	  <p class="pagetext">{$title_fastadd2}</p>
	  <div>{$input_fastadd2}<br />{$help_fastadd2}</div>
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
  <p>{$help_submit_template}<br /><br />
  {$sample_submit_template}</p>
  <div class="showhelp"><br />{$help_subtplvars}</div>
  </div>
 </div>
{$tab_end}
{$tabs_end}
 <div class="pageoverflow">
  <br />
  <p class="pageinput">{$save} {$cancel} {$apply}</p>
 </div>
{$form_end}

{if !empty($jsincs)}
{foreach from=$jsincs item=file}{$file}
{/foreach}{/if}
{if !empty($jsfuncs)}
<script type="text/javascript">
//<![CDATA[
{foreach from=$jsfuncs item=func}{$func}{/foreach}
//]]>
</script>
{/if}
