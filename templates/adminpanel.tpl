{if !empty($message)}{$message}{/if}
{$tabs_start}
{$formstab_start}
{if !empty($forms)}
{$start_formsform}
<div class="pageinput pageoverflow">
<table class="pagetable leftwards">
 <thead><tr>
  <th>{$title_name}</th>
  <th>{$title_alias}</th>
{if $pmod}
  <th class="pageicon"></th>
  <th class="pageicon"></th>
  <th class="pageicon"></th>
{/if}
  <th class="pageicon"></th>
  <th class="checkbox" style="width:20px;">{$selectall_forms}</th>
 </tr></thead>
 <tbody>
{foreach from=$forms item=entry}{cycle values='row1,row2' assign=rowclass}
 <tr class="{$rowclass}" onmouseover="this.className='{$rowclass}hover';" onmouseout="this.className='{$rowclass}';">
  <td>{$entry->name}</td>
  <td>{$entry->alias}</td>
{if $pmod}
  <td>{$entry->editlink}</td>
  <td>{$entry->copylink}</td>
  <td>{$entry->deletelink}</td>
{/if}
  <td>{$entry->exportlink}</td>
  <td class="checkbox">{$entry->selected}</td>
 </tr>
{/foreach}
</tbody>
</table>
</div>
{else}{*no $forms*}
<p class="pageinput">{$noforms}</p>
{/if}
<div class="pageinput" style="margin-top:1em;">
{if $pmod}<span style="margin-right:5em;">{$addlink}&nbsp;{$addform}</span>{/if}
{if !empty($forms)}{if $pmod}{$clonebtn} {$deletebtn} {/if}{$exportbtn}{/if}
</div>
{$form_end}
{$tab_end}
{if $pmod}
{$importstab_start}
<div class="pageinput pageoverflow">
{if isset($submitfb)}
 <fieldset>
 <legend>{$legend_xmlimport}</legend>
{/if}
 {$start_importxmlform}
 {foreach from=$xmls item=entry}
 {if isset($entry->title)}<p class="pagetext">{$entry->title}:</p>{/if}
  <div>{$entry->input}{if isset($entry->help)}<br />{$entry->help}{/if}</div>
 {/foreach}
 <br />
 <div>{$submitxml}</div>
 {$form_end}
{if isset($submitfb)}
 </fieldset>
 <fieldset>
 <legend>{$legend_fbimport}</legend>
 {$start_importfbform}
  <div>{$submitfb}{if isset($submitdata)} {$submitdata}{/if}</div>
 {$form_end}
 </fieldset>
{/if}
</div>
{$tab_end}
{/if}{*$pmod*}
{if $padm}
{$settingstab_start}
{$start_configform}
<div class="pageinput pageoverflow">
{foreach from=$configs item=entry}
{if isset($entry->title)}<p class="pagetext">{$entry->title}:</p>{/if}
<div>{$entry->input}{if isset($entry->help)}<br />{$entry->help}{/if}</div>
{/foreach}
<br />
<div>{$submitcfg}&nbsp;{$cancel}</div>
</div>
{$form_end}
{$tab_end}
{/if}
{$tabs_end}
