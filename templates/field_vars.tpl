<table class="pwf_legend"><thead>
<tr><th colspan="2">{$title_variables}</th></tr>
<tr><th>{$title_name}</th><th>{$title_field}</th></tr>
</thead><tbody>
{foreach from=$rows item=entry}
{cycle values='row1,row2' assign=rowclass}
<tr class="{$rowclass}" onmouseover="this.className='{$rowclass}hover';" onmouseout="this.className='{$rowclass}';">
<td>{$entry->id}</td><td>{$entry->name}</td></tr>
{/foreach}
</tbody></table>
