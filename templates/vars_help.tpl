<p style="font-weight:bold;">{$help_vars_title}:</p>
<table class="module_fb_legend">
<thead><tr>
<th>{$variable}</th><th>{$attribute}</th>
</tr></thead>
<tbody>
{foreach from=$sysfields item=entry}
{cycle values='odd,even' assign='rowclass'}
<tr class="{$rowclass}"><td>{$entry->name}</td><td>{$entry->title}</td></tr>
{/foreach}
{if !empty($subfields)}
 {foreach from=$subfields item=entry}
 {if $rowclass == 'even'}
  {cycle values='odd,even' assign='rowclass'}
 {else}
  {cycle values='even,odd' assign='rowclass'}
 {/if}
<tr class="{$rowclass}"><td>{ldelim}${$entry->name}{rdelim} / {ldelim}$fld_{$entry->id}{rdelim}</td><td>{$entry->title}</td></tr>
 {/foreach}
{/if}
</tbody>
</table>
{$help_other_fields}<br />
{if !empty($subfields)}
<br />{$help_field_object}:<br />
<table class="module_fb_legend">
{foreach from=$obfields item=entry}
{cycle values='odd,even' assign='rowclass'}
<tr class="{$rowclass}"><td>{$entry->name}</td><td>{$entry->title}</td></tr>
{/foreach}
</table>
{$help_object_example}
{/if}
