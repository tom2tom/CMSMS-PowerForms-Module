<h3>What does this module do?</h3>
<p>The module provides a powerful and flexible mechanism for creating forms to be displayed
on the website. Each form may contain many kinds of inputs, may have validation applied to
these inputs, and the results of the form may be handled in a variety of ways.</p>
<h3>How is it used?</h3>
<p>First, install it, then set relevant permissions.</p>
<h4>Creating a form</h4>
<p>Suitably authorised users will see a menu item called "Power Forms" in the admin "Extensions" menu.
Click that item. On the displayed page (at the bottom of the list of forms), there are objects
which can be activated to add a new form.</p>
<h4>Importing forms</h4>
<p>A form may be available as an XML file. Such file can be imported via the "Imports" tab.</p>
<h4>Importing FormBuilder forms</h4>
<p>Those 'old' forms can be imported via the "Imports" tab. <strong>However</strong>,
the imported forms' templates (i.e. main, submission, field-specific) are not entirely
compatible, and some manual attention will be needed. Specifically:</p>
<ul>
<li>Powerforms field-name aliases are shorter (max. 12 chars)</li>
<li>all 'captcha'-related content needs to be removed
(Powerforms has a Captcha field-type which replaces the FormBuilder general setting)</li>
</ul>
<h4>Displaying a form</h4>
<p>The tag used to display each form is shown in the module's admin page. Each tag is like
<code>{PWForms form='sample_form'}</code>. Placing the relevant tag into the content of
a website page or template will cause that form to be displayed.</p>
<h4>Use by other modules</h4>
<p>Any other module may initiate display of a form by code like<pre>
$pf = cms_utils::get_module('PWForms');
$parms = array(&lt;relevant data&gt;);
$pf->DoAction('show_form',$id,$parms,$returnid);</pre></p>
<p>Relevant $parms:
<ul>
<li>'form_id' => form enumerator</li>
<li>'preload' => (optional) set first-time field values, array, keys=field id or alias, values=what to set</li>
<li>'resume' => (optional) action name for redirect upon cancellation or submission</li>
<li>'passthru' => (optional) data to be provided to the redirect action</li>
<li>'exclude' => (optional) singleton or array of field id(s) or alias(es) to be omitted from the form</li>
<li>'excludetype' => (optional) singleton or array of field-type(s) to be omitted from the form</li>
</ul></p>
<p>$parms may also include form-template parameters e.g.
<ul>
<li>'in_admin'</li>
<li>'in_browser' DEPRECATED</li>
</ul></p>
<h4>Importing field-types</h4>
<p>Other modules may need field-type(s) that are not normally available.
Such a field can be implemented by creating a suitable class for the field, and
during installation/uninstallation of the other module, registering/deregistering
that class. The PowerBrowse module provides an example of this process in use.<p>
<p>Field-types to be used in forms may be registered by<pre>
$pf = cms_utils::get_module('PWForms');
$pf->RegisterField($classpath);
</pre>
and de-registered by<pre>
$pf = cms_utils::get_module('PWForms');
$pf->DeregisterField($classpath);
</pre>
where $classpath is the absolute file-system path of the file defining the field-class to be [de]registered.</p>
<p>Such field-classes must include public properties
<ul>
<li>$MenuKey set to the owner-module's Lang key for this field's menu label</li>
<li>$mymodule used for module-object reference</li>
</ul>
and may include whatever else is appropriate, including Fieldbase properties and sub-classed methods.</p>
<h3>Interactions</h3>
<p>The following modules may be used in relevant contexts, if available:</p>
<ul>
<li>CMSMailer (for CMSMS < 2.0)</li>
<li>Captcha</li>
<li>Cataloger</li>
<li>CompanyDirectory</li>
<li>FrontEndUsers</li>
<li>Uploads</li>
</ul>
<p>Designer-specified UDT's may be called for particular fields, and/or before, during and/or after processing a form.</p>
<h3>Styling</h3>
<p>Classes applied in relevant contexts are:</p>
<ul>
<li>optional designer-specified class for div or table containing all controls in a form</li>
<li>optional designer-specified class for controls in a field</li>
<li>error_list - applied to html div's and tables containing a list of error messages</li>
<li>error_message - applied to html div's containing controls to display an error message</li>
<li>help_display - applied to displayable html div's containing controls to display help</li>
<li>invalid_field - applied to controls in fields which have not validated successfully</li>
<li>required - applied to controls for which some input is compulsory</li>
<li>submit_actions - applied to html div containing submit/next/prev buttons</li>
<li>submit_current - applied to submit button on current page of a form</li>
<li>submit_next - applied to next-page-button on current page of a form</li>
<li>submit_prev - applied to previous-page-button on current page of a form</li>
</ul>
<h3>Permissions</h3>
<ul>
<li>Modify Power Forms - add/delete/modify any form</li>
<li>Modify PWForms module settings - modify any configuration setting</li>
</ul>
<h3>Events</h3>
<ul>
<li>OnFormDisplay - triggered when a form is displayed</li>
<li>OnFormSubmit - triggered when a form is submitted</li>
<li>OnFormSubmitError - triggered if there is an error when a form is submitted</li>
</ul>
<h3>Support</h3>
<p>This module is provided as-is. Please read the text of the license for the full disclaimer.
Just to be clear, there's no guarantee of support. However, there are some resources available
to help you with it:</p>
<ul>
<li>for the latest version of this module, or to file a bug report, go to the
<a href="http://dev.cmsmadesimple.org/projects/powerforms">module's Forge page</a></li>
<li>discussion of this module may be found in the
<a href="http://forum.cmsmadesimple.org">CMS Made Simple Forums</a></li>
<li>you may have some success emailing the author directly<br />
</li>
</ul>
<h3>Copyright and license</h3>
<p>Copyright &copy; 2012-2017 Tom Phane &lt;tpgww@onepost.net&gt;<br />
Derived from code copyright &copy; 2006-2012 Samuel Goldstein &lt;sjg@cmsmodules.com&gt;<br />
All rights reserved.</p>
<p>This module has been released under version 3 of the
<a href="http://www.gnu.org/licenses/licenses.html#AGPL">GNU Affero Public License</a>.
The module must not be used otherwise than in acccordance with that license.</p>
<h3>Requirements</h3>
<ul>
<li>PHP 5.4+</li>
<li>PHP Mcrypt extension</li>
<li>PHP curl extension is needed if a 'SubmitForm' field is used</li>
</ul>
<h3>Desirables</h3>
<ul>
<li>PHP mcrypt extension for protecting cached data when a 'EmailConfirmation' field is used</li>
<li>Cache operations are faster if at least one of these PHP cache modules: apc[u],[p]redis,wincache,xcache,yac is available</li>
</ul>
