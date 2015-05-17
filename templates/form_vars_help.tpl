<p style="font-weight:bold;">{$template_vars_title}:</p>
<table class="pwf_legend">
<thead><tr>
<th>{$variable_title}</th><th>{$attribute_title}</th>
</tr></thead>
<tbody>
{foreach from=$sysfields item=entry}
{cycle name=sysfields values='row1,row2' assign=rowclass}
<tr class="{$rowclass}" onmouseover="this.className='{$rowclass}hover';" onmouseout="this.className='{$rowclass}';">
<td>{$entry->name}</td><td>{$entry->title}</td></tr>
{/foreach}
{if !empty($subfields)}
 {foreach from=$subfields item=entry}
 {if $rowclass == 'row2'}
  {cycle name=subfields values='row1,row2' assign=rowclass}
 {else}
  {cycle name=subfields values='row2,row1' assign=rowclass}
 {/if}
<tr class="{$rowclass}" onmouseover="this.className='{$rowclass}hover';" onmouseout="this.className='{$rowclass}';">
<td>{ldelim}${$entry->name}{rdelim} / {ldelim}$fld_{$entry->id}{rdelim}</td><td>{$entry->title}</td></tr>
 {/foreach}
{/if}
</tbody>
</table>
{$help_other_fields}<br />
{if !empty($subfields)}
<br />{$help_field_values}:<br />
<table class="pwf_legend">
{foreach from=$obfields item=entry}
{cycle name=obfields values='row1,row2' assign=rowclass}
<tr class="{$rowclass}" onmouseover="this.className='{$rowclass}hover';" onmouseout="this.className='{$rowclass}';">
<td>{$entry->name}</td><td>{$entry->title}</td></tr>
{/foreach}
</table>
{$help_object_example}
{/if}
