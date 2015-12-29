{if isset($message)}{$message}{/if}

{$backtomod_nav}&nbsp;{$backtoform_nav}<br />
{$form_start}
{if isset($tabs_start)}
{$tabs_start}
{if isset($maintab_start)}
{$maintab_start}
 <div class="pageinput pageoverflow">
{foreach from=$mainList item=entry}
{if isset($entry->title)}<p class="pagetext">{$entry->title}:</p>{/if}
{if isset($entry->input)}<div>{$entry->input}</div>{/if}
{if isset($entry->help)}<p>{$entry->help}</p>{/if}
{/foreach}
{if !empty($mainvarhelp)}<br /><div>{$help_subtplvars}</div>{/if}
{if isset($mainTable)}
  <br />
  <div class="pageinput pageoverflow">
   <table class="pagetable leftwards">
 {section name=r loop=$mainTable}
  {if $smarty.section.r.first}
   <thead>
   <tr class="pagetext">{section name=c loop=$mainTable[r]}<th>{$mainTable[r][c]}</th>{/section}</tr>
   </thead>
   <tbody>
  {else}
	 <tr class="row1">{section name=c loop=$mainTable[r]}<td>{$mainTable[r][c]}</td>{/section}</tr>
  {/if}
 {/section}
   </tbody>
   </table>
  </div>
 {if $add || $del}
  <br />
  <p class="pageinput">{if $add}{$add}&nbsp;{/if}{if $del}{$del}{/if}</p>
 {/if}
 {/if}
 </div>
{$tab_end}
{/if}{*isset($maintab_start)*}
{if isset($advancedtab_start)}
{$advancedtab_start}
 <div class="pageinput pageoverflow">
{foreach from=$advList item=entry}
{if isset($entry->title)}<p class="pagetext">{$entry->title}:</p>{/if}
{if isset($entry->input)}<div>{$entry->input}</div>{/if}
{if isset($entry->help)}<p>{$entry->help}</p>{/if}
{/foreach}
{if !empty($advvarhelp)}<br /><div>{$help_subtplvars}</div>{/if}
 </div>
{$tab_end}
{/if}{*isset($advancedtab_start)*}
{$tabs_end}
{else}{*!isset($tabs_start)*}
{if isset($mainitem->title)}<p class="pagetext">{$mainitem->title}:</p>{/if}
{if isset($mainitem->input)}<div class="pageinput">{$mainitem->input}</div>{/if}
{if isset($mainitem->help)}<p class="pageinput">{$mainitem->help}</p>{/if}
{/if}
 <br />
 <p class="pageinput">{if isset($submit)}{$submit}&nbsp;{/if}{$cancel}</p>
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
