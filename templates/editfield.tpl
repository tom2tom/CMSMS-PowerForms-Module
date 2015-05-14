{if isset($message)}{$message}{/if}

{$backtomod_nav}&nbsp;{$backtoform_nav}<br />
{$form_start}{if isset($op)}{$op}{/if}
{$tabs_start}
{$maintab_start}
 <div class="pageoverflow">
{foreach from=$mainList item=entry}
{if isset($entry->title)}<p class="pagetext">{$entry->title}:</p>{/if}
{if isset($entry->input)}<p class="pageinput">{$entry->input}</p>{/if}
{if isset($entry->help)}<p class="pageinput">{$entry->help}</p>{/if}
{/foreach}
{if !empty($mainvarhelp)}<br /><div class="pageinput">{$help_vars}</div>{/if}
{if isset($mainTable)}
   <br />
   <table class="pwf_table pageinput">
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
	 {if $add != '' or $del != ''}
    <br />
    <p class="pageinput">{$add}&nbsp;{$del}</p>
 {/if}
 {/if}
 </div>
{$tab_end}
{$advancedtab_start}
 <div class="pageoverflow">
{foreach from=$advList item=entry}
{if isset($entry->title)}<p class="pagetext">{$entry->title}:</p>{/if}
{if isset($entry->input)}<p class="pageinput">{$entry->input}</p>{/if}
{if isset($entry->help)}<p class="pageinput">{$entry->help}</p>{/if}
{/foreach}
{if !empty($advvarhelp)}<br /><div class="pageinput">{$help_vars}</div>{/if}
 </div>
{$tab_end}
{$tabs_end}
<div class="pageoverflow">
 <br />
 <p class="pageinput">{if isset($submit)}{$submit}&nbsp;{/if}{$cancel}</p>
</div>
{$form_end}
{if !empty($jsfuncs)}
<script type="text/javascript">
//<![CDATA[
{foreach from=$jsfuncs item=func}{$func}{/foreach}
//]]>
</script>
{/if}
