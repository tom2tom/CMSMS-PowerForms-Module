<?php
# This file is part of CMS Made Simple module: PWForms
# Copyright (C) 2012-2017 Tom Phane <tpgww@onepost.net>
# Refer to licence and other details at the top of file PWForms.module.php
# More info at http://dev.cmsmadesimple.org/projects/powerforms
/*
To generate encoded templates for default forms, run this file from the command line.
After that, the results will need to be manually transcribed into the relevant
template .xml files, at the respective <*_template>....</*_template>
*/
$templates = [];

$templates['Sample_form'] =<<<EOS
{if \$form_done}
	{* This section is for displaying submission-errors *}
	{if \$submission_error}
		<div class="error_message">{\$submission_error}</div>
		{if \$show_submission_errors}
			<div class="error_list">
			<ul>
			{foreach \$submission_error_list as \$one}
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
		{foreach \$form_validation_errors as \$one}
			<li>{\$one}</li>
		{/foreach}
		</ul>
		</div>
	{/if}
	{* and now the form itself *}
	<div{if \$css_class} class="{\$css_class}"{/if}>
	{if \$total_pages gt 1}<span>{\$title_page_x_of_y}</span>{/if}
	{foreach \$fields as \$one}
		{strip}
		{if \$one->display}
			{if \$one->needs_div}<div>{/if}
			{if !\$one->hide_name}
				<label{if !\$one->multiple_parts} for="{\$one->input_id}"{/if}>{\$one->name}
					{if \$one->required_symbol}{\$one->required_symbol}{/if}
				</label>
			{/if}
			{if \$one->multiple_parts}
				{foreach \$one->input as \$part}
				{if \$one->label_parts}
					<div>{\$part->input}&nbsp;{\$part->name}</div>
				{else}
					{\$part->input}
				{/if}
				{if !empty(\$part->op)}{\$part->op}{/if}
				{/foreach}
			{else}
				{if \$one->smarty_eval}{eval var=\$one->input}{else}{\$one->input}{/if}
			{/if}
			{if !\$one->valid} &lt;--- {\$one->error}{/if}
			{if \$one->needs_div}</div>{/if}
		{/if}
		{/strip}
	{/foreach}
	<div class="submit_actions">{\$prev} {\$submit}</div>
	</div>
{/if}
EOS;

$templates['Sample_submission'] =<<<EOS
<h1>Thanks!</h1>
<p>Your feedback helps make the PWForms module better.</p>
EOS;

$templates['Sample_email'] =<<<EOS
<h1>Someone's Testing a PWForms Submission!</h1>
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

$templates['Sample_captcha'] =<<<EOS
{\$prompt}<br />{\$captcha_input}<br />{\$captcha}
EOS;

$templates['Contact_form'] =<<<EOS
{if \$form_done}
	{* This section is for displaying submission errors *}
	{if !empty(\$submission_error)}
		<div class="error_message">{\$submission_error}</div>
		{if !empty(\$show_submission_errors)}
			<div class="error_list">
			<ul>
			{foreach \$submission_error_list as \$one}
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
		{foreach \$form_validation_errors as \$one}
			<li>{\$one}</li>
		{/foreach}
		</ul>
		</div>
	{/if}
	{* and now the form itself *}
	<div{if \$css_class} class="{\$css_class}"{/if}>
	{if \$total_pages gt 1}<span>{\$title_page_x_of_y}</span>{/if}
	{foreach \$fields as \$one}
	{strip}
		{if \$one->display}
			{if \$one->needs_div}<div>{/if}
			{if !\$one->hide_name}
				<label{if !\$one->multiple_parts} for="{\$one->input_id}"{/if}>{\$one->name}
				{if \$one->required_symbol}{\$one->required_symbol}{/if}
				</label>
			{/if}
			{if \$one->multiple_parts}
				{foreach \$one->input as \$part}
				{if \$one->label_parts}
					<div>{\$part->input}&nbsp;{\$part->name}</div>
				{else}
					{\$part->input}
				{/if}
				{if !empty(\$part->op)}{\$part->op}{/if}
				{/foreach}
			{else}
				{if \$one->smarty_eval}{eval var=\$one->input}{else}{\$one->input}{/if}
			{/if}
			{if !\$one->valid} &lt;--- {\$one->error}{/if}
			{if \$one->needs_div}</div>{/if}
		{/if}
	{/strip}
	{/foreach}
	<div class="submit_actions">{\$prev} {\$submit}</div>
	</div>
{/if}
EOS;

$templates['Contact_submission'] =<<<EOS
<p>Thank you, <strong>{\$your_name}</strong>.</p>
<p>Your submission has been successful. You may wish to print this page as a reference.</p>
<h3>Contact Details</h3>
<p>
<strong>Name</strong>: {\$your_name}<br />
<strong>Email</strong>: <a href="mailto:{\$your_email_a}">{\$your_email_a}</a>
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

$templates['Contact_email'] =<<<EOS
PWForms Submission
Date submitted: {\$sub_date}
IP address from which the form was submitted: {\$sub_source}
Form server: {\$form_host}
URL of page containing form: {\$form_url}
Form name: {\$form_name}
PWForms version: {\$version}
----------------------------------------------
Your name: {\$your_name}
Your email address: {\$your_email_a}
Subject: {\$subject}
Message: {\$message}
EOS;

$templates['Contact_captcha'] =<<<EOS
{\$prompt}<br />{\$captcha_input}<br />{\$captcha}
EOS;

$templates['Advanced_form'] =<<<EOS
{if \$form_done}
	{* This section is for displaying submission errors *}
	{if !empty(\$submission_error)}
		<div class="error_message">{\$submission_error}</div>
		{if !empty(\$show_submission_errors)}
			<div class="error_list">
			<ul>
			{foreach \$submission_error_list as \$one}
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
		{foreach \$form_validation_errors as \$one}
			<li>{\$one}</li>
		{/foreach}
		</ul>
		</div>
	{/if}
	{* and now the form itself *}
	<h4 style="text-align:center;">Order</h4>
	<div{if \$css_class} class="{\$css_class}"{/if}>
	{if \$total_pages gt 1}<span>{\$title_page_x_of_y}</span>{/if}
	{foreach \$fields as \$one}
		{strip}
		{if \$one->display}
			{if \$one->needs_div}<div>{/if}
			{if !\$one->hide_name}
				<label{if !\$one->multiple_parts} for="{\$one->input_id}"{/if}>{\$one->name}
				{if \$one->required_symbol}{\$one->required_symbol}{/if}
				</label>
			{/if}
			{if \$one->multiple_parts}
				{foreach \$one->input as \$part}
				{if \$one->label_parts}
					<div>{\$part->input}&nbsp;{\$part->name}</div>
				{else}
					{\$part->input}
				{/if}
				{if !empty(\$part->op)}{\$part->op}{/if}
				{/foreach}
			{else}
				{if \$one->smarty_eval}{eval var=\$one->input}{else}{\$one->input}{/if}
			{/if}
			{if !\$one->valid} &lt;--- {\$one->error}{/if}
			{if \$one->needs_div}</div>{/if}
		{/if}
		{/strip}
	{/foreach}
	<div class="submit_actions">{\$prev} {\$submit}</div>
	</div>
{/if}
EOS;

$templates['Advanced_submission'] =<<<EOS
<p>Thank you, <strong>{\$your_name}</strong>.</p>
<p>Your submission has been successful. You may wish to print this page as a reference.</p>
<h3>Contact Details</h3>
<p>
<strong>Name</strong>: {\$your_name}<br />
<strong>Email</strong>: <a href="mailto:{\$your_email_a}">{\$your_email_a}</a>
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

$templates['Advanced_email'] =<<<EOS
PWForms Submission
Date submitted: {\$sub_date}
IP address from which form was submitted: {\$sub_source}
Form host: {\$form_host}
URL of page containing form: {\$form_url}
Form name: {\$form_name}
PWForms version: {\$version}
----------------------------------------------
Your name: {\$your_name}
Your email address: {\$your_email}
Subject: {\$subject}
Message: {\$message}
EOS;

$templates['Advanced_captcha'] =<<<EOS
{\$prompt}<br />{\$captcha_input}<br />{\$captcha}
EOS;

$fh = fopen(__DIR__.DIRECTORY_SEPARATOR.'encoded-templates.xml', 'w');
foreach ($templates as $key=>&$value) {
	fwrite($fh, '<'.$key.'_template>'.urlencode($value).'</'.$key.'_template>'.PHP_EOL.PHP_EOL);
}

unset($value);
fclose($fh);
