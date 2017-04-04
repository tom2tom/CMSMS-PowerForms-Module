{if isset($message)}{$message}{/if}
{$backtomod_nav}&nbsp;{$backtoform_nav}<br />
{$form_start}
{if isset($tabs_start)}
{$tabs_start}
{if isset($maintab_start)}
{$maintab_start}
 <div class="pageinput pageoverflow">
{foreach from=$mainList item=entry}
{if !empty($entry->title)}<p class="pagetext">{$entry->title}:</p>{/if}
{if isset($entry->input)}<div>{$entry->input}</div>{/if}
{if !empty($entry->help)}<p>{$entry->help}</p>{/if}
{/foreach}
{if !empty($mainvarhelp)}<br /><div>{$help_subtplvars}</div>{/if}
{if isset($multiControls)}
  <div class="pageoverflow">
   <table id="controls" class="pagetable leftwards">
 {*section name=r loop=$multiControls*}
 {foreach $multiControls as $r}
 {cycle name=multiControls values='row2,row1' assign=rowclass}
  {*if $smarty.section.r.first*}
  {if $r@first}
   <thead>
   <tr class="pagetext">
   {*section name=c loop=$multiControls[r]}<th>{$multiControls[r][c]}</th>{/section*}
   {foreach $multiControls[$r] as $c}<th>{$multiControls[$r][$c]}</th>{/foreach}
   </tr>
   </thead>
   <tbody>
  {else}
   <tr class="{$rowclass}" onmouseover="this.className='{$rowclass}hover';" onmouseout="this.className='{$rowclass}';">
   {*section name=c loop=$multiControls[r]}<td>{$multiControls[r][c]}</td>{/section*}
   {foreach $multiControls[$r] as $c}<td>{$multiControls[$r][$c]}</td>{/foreach}
   </tr>
  {/if}
 {*/section*}
 {/foreach}
   </tbody>
   </table>
 {if isset($dndhelp)}<div id="helpdnd" style="margin:1em 0;display:none;">{$dndhelp}</div>{/if}
  </div>
 {if $add || $del}
  <br />
  <p>{if $add}{$add}&nbsp;{/if}{if $del}{$del}{/if}</p>
 {/if}
 {/if}
 </div>
{$tab_end}
{/if}{*isset($maintab_start)*}
{if isset($advancedtab_start)}
{$advancedtab_start}
 <div class="pageinput pageoverflow">
{foreach from=$advList item=entry}
{if !empty($entry->title)}<p class="pagetext">{$entry->title}:</p>{/if}
{if isset($entry->input)}<div>{$entry->input}</div>{/if}
{if !empty($entry->help)}<p>{$entry->help}</p>{/if}
{/foreach}
{if !empty($advvarhelp)}<br /><div>{$help_subtplvars}</div>{/if}
 </div>
{$tab_end}
{/if}{*isset($advancedtab_start)*}
{$tabs_end}
{else}{*!isset($tabs_start)*}
{if !empty($mainitem->title)}<p class="pagetext">{$mainitem->title}:</p>{/if}
{if isset($mainitem->input)}<div class="pageinput">{$mainitem->input}</div>{/if}
{if !empty($mainitem->help)}<p class="pageinput">{$mainitem->help}</p>{/if}
{/if}
 <br />
 <p class="pageinput">{if isset($submit)}{$submit}&nbsp;{/if}{$cancel}</p>
{$form_end}
