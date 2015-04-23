{if $message != ''}<div class="pagemcontainer"><p class="pagemessage">{$message}</p></div>{/if}

{$tabheaders}
{$start_formtab}
<table class="pagetable pwf_table">
 <thead><tr>
  <th>{$title_form_name}</th>
  <th>{$title_page_tag}</th>
  <th class="pageicon">&nbsp;</th>
  <th class="pageicon">&nbsp;</th>
  <th class="pageicon">&nbsp;</th>
  <th class="pageicon">&nbsp;</th>
 </tr></thead>
 <tbody>
{foreach from=$forms item=entry}
 {cycle values='odd,even' assign=rowclass}
 <tr class="{$rowclass}" onmouseover="this.className='{$rowclass}hover';" onmouseout="this.className='{$rowclass}';">
  <td>{$entry->name}</td>
  <td>{ldelim}PowerForms form='{$entry->usage}'{rdelim}</td>
  <td>{$entry->editlink}</td>
  <td>{$entry->copylink}</td>
  <td>{$entry->deletelink}</td>
  <td>{$entry->xmllink}</td>
 </tr>
{/foreach}
</tbody>
</table>
{if $addlink != ''}
<br /><p class="pageinput">{$addlink}&nbsp;{$addform}</p>
{/if}
<br />
<fieldset>
<legend>{$legend_xml_import}</legend>
{$start_xmlform}
<div class="pageoverflow">
 <p class="pagetext">{$title_xml_to_upload}:</p>
 <p class="pageinput">{$input_xml_to_upload}</p>
 <p class="pagetext">{$title_xml_upload_formname}:</p>
 <p class="pageinput">{$input_xml_upload_formname}&nbsp;<em>{$info_leaveempty}</em></p>
 <p class="pagetext">{$title_xml_upload_formalias}:</p>
 <p class="pageinput">{$input_xml_upload_formalias}&nbsp;<em>{$info_leaveempty}</em></p>
 <br />
 <p class="pageinput">{$submitxml}</p>
</div>
{$end_xmlform}
</fieldset>
{$end_tab}
{$start_configtab}

{if $may_config == 1}
{$start_configform}
<div class="pageoverflow">
 <p class="pagetext">{$title_enable_fastadd}:</p>
 <p class="pageinput">{$input_enable_fastadd}</p>
 <p class="pagetext">{$title_hide_errors}:</p>
 <p class="pageinput">{$input_hide_errors}</p>
 <p class="pagetext">{$title_require_fieldnames}:</p>
 <p class="pageinput">{$input_require_fieldnames}</p>
 <p class="pagetext">{$title_unique_fieldnames}:</p>
 <p class="pageinput">{$input_unique_fieldnames}</p>
 <p class="pagetext">{$title_blank_invalid}:</p>
 <p class="pageinput">{$input_blank_invalid}</p>
 <p class="pagetext">{$title_relaxed_email_regex}:</p>
 <p class="pageinput">{$input_relaxed_email_regex}</p>
 <p class="pagetext">{$title_show_version}:</p>
 <p class="pageinput">{$input_show_version}</p>
 <p class="pagetext">{$title_enable_antispam}:</p>
 <p class="pageinput">{$input_enable_antispam}</p>
 <p class="pagetext">{$title_show_fieldids}:</p>
 <p class="pageinput">{$input_show_fieldids}</p>
 <p class="pagetext">{$title_show_fieldaliases}:</p>
 <p class="pageinput">{$input_show_fieldaliases}</p>
 <br />
 <p class="pageinput">{$submit}</p>
</div>
{$end_configform}

{else}
	<p>{$no_permission}</p>
{/if}
{$end_tab}
{$end_tabs}
