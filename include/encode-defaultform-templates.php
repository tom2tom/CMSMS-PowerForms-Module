<?php
# This file is part of CMS Made Simple module: PowerForms
# Copyright (C) 2012-2015 Tom Phane <tpgww@onepost.net>
# Refer to licence and other details at the top of file PowerForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms
/*
Temporarily include this somewhere, to generate encoded templates for default forms
After running, the results will need to be manually transcribed into the relevant template .xml files
*/
$templates = array();

$templates['Sample_submission'] =<<<EOS
<h1>Thanks!</h1>
<p>Your feedback helps make the PowerForms module better.</p>
EOS;

$templates['Sample_form'] =<<<EOS
{* DEFAULT FORM LAYOUT / pure CSS *}
{if \$form_done}
	{* This section is for displaying submission-errors *}
	{if \$submission_error}
		<div class="error_message">{\$submission_error}</div>
		{if \$show_submission_errors}
			<div class="error">
			<ul>
			{foreach from=\$submission_error_list item=one}
				<li>{\$one}</li>
			{/foreach}
			</ul>
		</div>
		{/if}
	{/if}
{else}
	{* This section is for displaying the form *}
	{* we start with validation errors *}
	{if \$form_has_validation_errors}
		<div class="error_message">
		<ul>
		{foreach from=\$form_validation_errors item=one}
			<li>{\$one}</li>
		{/foreach}
		</ul>
		</div>
	{/if}
	{* and now the form itself *}
	{\$form_start}
	<div>{\$hidden}</div>
	<div{if \$css_class} class="{\$css_class}"{/if}>
	{if \$total_pages gt 1}<span>{\$title_page_x_of_y}</span>{/if}
	{foreach from=\$fields item=one}
		{strip}
		{if \$one->display}
			{if \$one->needs_div}
				<div
					{if \$one->required || \$one->css_class} class="
						{if \$one->required}required{/if}
						{if \$one->css_class}{\$one->css_class}{/if}
						"
					{/if}
				>
			{/if}
			{if !\$one->hide_name}
				<label{if \$one->multiple_parts != 1} for="{\$one->input_id}"{/if}>{\$one->name}
					{if \$one->required_symbol}{\$one->required_symbol}{/if}
				</label>
			{/if}
			{if \$one->multiple_parts}
				{section name=numloop loop=\$one->input}
				{if \$one->label_parts}
					<div>{\$one->input[numloop]->input}&nbsp;{\$one->input[numloop]->name}</div>
				{else}
					{\$one->input[numloop]->input}
				{/if}
				{if !empty(\$one->input[numloop]->op)}{\$one->input[numloop]->op}{/if}
				{/section}
			{else}
				{if \$one->smarty_eval}{eval var=\$one->input}{else}{\$one->input}{/if}
			{/if}
			{if !\$one->valid} &lt;--- {\$one->error}{/if}
			{if \$one->needs_div}
				</div>
			{/if}
		{/if}
		{/strip}
	{/foreach}
	<div class="submit">{\$prev} {\$submit}</div>
	</div>
	{\$form_end}
	{\$jscript}
{/if}
EOS;

$templates['Sample_email'] =<<<EOS
<h1>Someone's Testing a PowerForms Submission!</h1>
<strong>Date submitted</strong>: {\$sub_date}<br />
<strong>IP address from which form was submitted</strong>: {\$sub_source}<br />
<strong>Form server</strong>: {\$form_host}<br />
<strong>URL of page containing form</strong>: {\$form_url}<br />
<strong>Form name</strong>: {\$form_name}<br />
<hr />
<strong>Modules you'll be using together</strong>: {\$modules_you}<br />
<strong>What will you personally be doing on your CMS MS site?</strong>: {\$what_will_yo}<br />
<strong>Where are you from?</strong>: {\$where_are_yo}<br />
<strong>Do you have any comments / feedback for me?</strong>: {\$do_you_have}
EOS;

$templates['Contact_submission'] =<<<EOS
<p>Thank you, <strong>{\$your_name}</strong>.</p>
<p>Your submission has been successful. You may wish to print this page as a reference.</p>
<h3>Contact Details</h3>
<p>
<strong>Name</strong>: {\$your_name}<br />
<strong>Email</strong>: <a href="mailto:{\$your_email_a}">{\$your_email_a}</a><br />
</p>
<h3>Feedback Details</h3>
<p>
<strong>Subject</strong>: {\$subject}<br />
<strong>Comments</strong>:<br />
{\$message}
</p> 
<h4>Other information</h4>
<p>
<strong>Date submitted</strong>: {\$sub_date}<br />
<strong>IP address from which the form was submitted</strong>: {\$sub_source}<br />
<strong>Form server</strong>: {\$form_host}<br />
<strong>URL of page containing form</strong>: {\$form_url}<br />
<strong>Form name</strong>: {\$form_name}
</p>
EOS;

$templates['Contact_form'] =<<<EOS
{* DEFAULT FORM LAYOUT / pure CSS *}
{if \$form_done}
	{* This section is for displaying submission errors *}
	{if !empty(\$submission_error)}
		<div class="error_message">{\$submission_error}</div>
		{if !empty(\$show_submission_errors)}
			<div class="error">
			<ul>
			{foreach from=\$submission_error_list item=one}
				<li>{\$one}</li>
			{/foreach}
			</ul>
		</div>
		{/if}
	{/if}
{else}
	{* This section is for displaying the form *}
	{* we start with validation errors *}
	{if !empty(\$form_has_validation_errors)}
		<div class="error_message">
		<ul>
		{foreach from=\$form_validation_errors item=one}
			<li>{\$one}</li>
		{/foreach}
		</ul>
		</div>
	{/if}
	{* and now the form itself *}
	{\$form_start}
	<div>{\$hidden}</div>
	<div{if \$css_class} class="{\$css_class}"{/if}>
	{if \$total_pages gt 1}<span>{\$title_page_x_of_y}</span>{/if}
	{foreach from=\$fields item=one}
	{strip}
		{if \$one->display}
			{if \$one->needs_div}
				<div
{if \$one->required || \$one->css_class || !\$one->valid} class="
{if \$one->required}required {/if}{if \$one->css_class}{\$one->css_class} {/if}{if !\$one->valid}fieldbad{/if}
"
{/if}
				>
			{/if}
			{if !\$one->hide_name}
				<label{if \$one->multiple_parts != 1} for="{\$one->input_id}"{/if}>{\$one->name}
				{if \$one->required_symbol}{\$one->required_symbol}{/if}
				</label>
			{/if}
			{if \$one->multiple_parts}
				{section name=numloop loop=\$one->input}
					{if \$one->label_parts}
						<div>{\$one->input[numloop]->input}&nbsp;{\$one->input[numloop]->name}</div>
					{else}
						{\$one->input[numloop]->input}
					{/if}
					{if !empty(\$one->input[numloop]->op)}{\$one->input[numloop]->op}{/if}
				{/section}
			{else}
				{if \$one->smarty_eval}{eval var=\$one->input}{else}{\$one->input}{/if}
			{/if}
			{if !\$one->valid} &lt;--- {\$one->error}{/if}
			{if \$one->needs_div}
				</div>
			{/if}
		{/if}
	{/strip}
	{/foreach}
	<div class="submit">{\$prev} {\$submit}</div>
	</div>
	{\$form_end}
	{\$jscript}
{/if}
EOS;

$templates['Contact_email'] =<<<EOS
PowerForms Submission
Date submitted: {\$sub_date}
IP address from which the form was submitted: {\$sub_source}
Form server: {\$form_host}
URL of page containing form: {\$form_url}
Form name: {\$form_name}
PowerForms version: {\$version}
----------------------------------------------
Your name: {\$your_name}
Your email address: {\$your_email_a}
Subject: {\$subject}
Message: {\$message}
EOS;

$templates['Advanced_submission'] =<<<EOS
<p>Thank you, <strong>{\$your_name}</strong>.</p>
<p>Your submission has been successful. You may wish to print this page as a reference.</p>
<h3>Contact Details</h3>
<p>
<strong>Name</strong>: {\$your_name}<br />
<strong>Email</strong>: <a href="mailto:{\$your_email_a}">{\$your_email_a}</a><br />
</p>
<h3>Feedback Details</h3>
<p>
<strong>Subject</strong>: {\$subject}<br />
<strong>Comments</strong>:<br />
{\$message}
</p> 
<h4>Other information</h4>
<p>
<strong>Date submitted</strong>: {\$sub_date}<br />
<strong>IP address from which form was submitted</strong>: {\$sub_source}<br />
<strong>Form host</strong>: {\$form_host}<br />
<strong>URL of page containing form</strong>: {\$form_url}<br />
<strong>Form name</strong>: {\$form_name}
</p>
EOS;

$templates['Advanced_form'] =<<<EOS
{* DEFAULT FORM LAYOUT / pure CSS *}
{if \$form_done}
	{* This section is for displaying submission errors *}
	{if !empty(\$submission_error)}
		<div class="error_message">{\$submission_error}</div>
		{if !empty(\$show_submission_errors)}
			<div class="error">
			<ul>
			{foreach from=\$submission_error_list item=one}
				<li>{\$one}</li>
			{/foreach}
			</ul>
		</div>
		{/if}
	{/if}
{else}
	{* This section is for displaying the form *}
	{* we start with validation errors *}
	{if !empty(\$form_has_validation_errors)}
		<div class="error_message">
		<ul>
		{foreach from=\$form_validation_errors item=one}
			<li>{\$one}</li>
		{/foreach}
		</ul>
		</div>
	{/if}
	{* and now the form itself *}
	{\$form_start}
	<h4 style="text-align:center;">Order</h4>
	{\$hidden}
	<div{if \$css_class} class="{\$css_class}"{/if}>
	{if \$total_pages gt 1}<span>{\$title_page_x_of_y}</span>{/if}
	{foreach from=\$fields item=one}
		{strip}
		{if \$one->display}
			{if \$one->needs_div}
				<div
				{if \$one->required || \$one->css_class || !\$one->valid} class="
{if \$one->required}required{/if}{if \$one->css_class} {\$one->css_class}{/if}{if !\$one->valid} fieldbad{/if}
"
				{/if}
				>
			{/if}
			{if !\$one->hide_name}
				<label{if \$one->multiple_parts != 1} for="{\$one->input_id}"{/if}>{\$one->name}
				{if \$one->required_symbol}
					{\$one->required_symbol}
				{/if}
				</label>
			{/if}
			{if \$one->multiple_parts}
				{section name=numloop loop=\$one->input}
					{if \$one->label_parts}
						<div>{\$one->input[numloop]->input}&nbsp;{\$one->input[numloop]->name}</div>
					{else}
						{\$one->input[numloop]->input}
					{/if}
					{if !empty(\$one->input[numloop]->op)}{\$one->input[numloop]->op}{/if}
				{/section}
			{else}
				{if \$one->smarty_eval}{eval var=\$one->input}{else}{\$one->input}{/if}
			{/if}
			{if !\$one->valid} &lt;--- {\$one->error}{/if}
			{if \$one->needs_div}
				</div>
			{/if}
		{/if}
		{/strip}
	{/foreach}
	<div class="submit">{\$prev} {\$submit}</div>
	</div>
	{\$form_end}
	{\$jscript}
{/if}
EOS;

$templates['Advanced_email'] =<<<EOS
PowerForms Submission
Date submitted: {\$sub_date}
IP address from which form was submitted: {\$sub_source}
Form host: {\$form_host}
URL of page containing form: {\$form_url}
Form name: {\$form_name}
PowerForms version: {\$version}
----------------------------------------------
Your name: {\$your_name}
Your email address: {\$your_email}
Subject: {\$subject}
Message: {\$message}
EOS;

$fh = fopen(dirname(__FILE__).DIRECTORY_SEPARATOR.'encoded-templates.xml','w');
foreach ($templates as $key=>&$value)
{
	fwrite($fh,"<{$key}_template>]][[".urlencode($value)."</{$key}_template>\n\n");
}

unset($value);
fclose($fh);

?>
