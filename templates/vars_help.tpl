<p style="font-weight:bold;">{$help_vars_title}:</p>
<table class="pwf_legend">
<thead><tr>
<th>{$variable}</th><th>{$attribute}</th>
</tr></thead>
<tbody>
{foreach from=$sysfields item=entry}
{cycle name=sysfields values='odd,even' assign=rowclass}
<tr class="{$rowclass}" onmouseover="this.className='{$rowclass}hover';" onmouseout="this.className='{$rowclass}';">
<td>{$entry->name}</td><td>{$entry->title}</td></tr>
{/foreach}
{if !empty($subfields)}
 {foreach from=$subfields item=entry}
 {if $rowclass == 'even'}
  {cycle name=subfields values='odd,even' assign=rowclass}
 {else}
  {cycle name=subfields values='even,odd' assign=rowclass}
 {/if}
<tr class="{$rowclass}" onmouseover="this.className='{$rowclass}hover';" onmouseout="this.className='{$rowclass}';">
<td>{ldelim}${$entry->name}{rdelim} / {ldelim}$fld_{$entry->id}{rdelim}</td><td>{$entry->title}</td></tr>
 {/foreach}
{/if}
</tbody>
</table>
{$help_other_fields}<br />
{if !empty($subfields)}
<br />{$help_field_object}:<br />
<table class="pwf_legend">
{foreach from=$obfields item=entry}
{cycle name=obfields values='odd,even' assign=rowclass}
<tr class="{$rowclass}" onmouseover="this.className='{$rowclass}hover';" onmouseout="this.className='{$rowclass}';">
<td>{$entry->name}</td><td>{$entry->title}</td></tr>
{/foreach}
</table>
{$help_object_example}
{/if}
