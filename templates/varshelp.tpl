<p style="font-weight:bold;">{$template_vars_title}:</p>
<table class="legend">
<thead><tr>
<th>{$variable_title}</th><th>{$property_title}</th>
</tr></thead>
<tbody>
{foreach $globalvars as $entry}
{cycle name=globalvars values='row1,row2' assign=rowclass}
<tr class="{$rowclass}" onmouseover="this.className='{$rowclass}hover';" onmouseout="this.className='{$rowclass}';">
<td>{$entry->name}</td><td>{$entry->title}</td></tr>
{/foreach}
{if !empty($fieldvars)}
 {foreach $fieldvars as $entry}
 {if $rowclass == 'row2'}
  {cycle name=fieldvars values='row1,row2' assign=rowclass}
 {else}
  {cycle name=fieldvars values='row2,row1' assign=rowclass}
 {/if}
<tr class="{$rowclass}" onmouseover="this.className='{$rowclass}hover';" onmouseout="this.className='{$rowclass}';">
<td>{ldelim}${$entry->name}{rdelim} / {ldelim}$fld_{$entry->id}{rdelim}</td><td>{$entry->title}</td></tr>
 {/foreach}
{/if}
</tbody>
</table>
{$help_other_fields}
