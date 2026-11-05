const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const { JSDOM } = require('jsdom');
const jquery = require('jquery');

function browser(asset) {
	const dom = new JSDOM(`<!doctype html><form data-id="one" data-url="/" data-error="Try again">
		<input name="an_mc_submit" value="1" type="hidden">
		<input name="form_email" type="email" required value="user@example.com">
		<input class="mpam-submit" type="submit" value="Subscribe"></form>`, {
		url: 'https://example.test/', runScripts: 'outside-only'
	});
	const $ = jquery(dom.window);
	dom.window.jQuery = $;
	const requests = [];
	$.ajax = options => {
		const deferred = $.Deferred();
		requests.push({ options, deferred });
		return deferred.promise();
	};
	dom.window.eval(fs.readFileSync(asset, 'utf8'));
	const form = dom.window.document.querySelector('form');
	const submit = () => form.dispatchEvent(new dom.window.Event('submit', { bubbles: true, cancelable: true }));
	return { dom, $, form, requests, submit };
}

for (const asset of ['assets/js/another-mailchimp.js', 'assets/js/another-mailchimp.min.js']) {
	test(`${asset}: keyboard/form submission, duplicate suppression and retries`, () => {
		const { dom, $, form, requests, submit } = browser(asset);
		try {
			for (let i = 0; i < 3; ++i) $(form).an_mc_subscribe_to_mail_chimp({ url: '/subscriptions' });
			submit(); // A submit event requires no preceding mouse click.
			submit();
			assert.equal(requests.length, 1);
			assert.equal(requests[0].options.method, 'POST');
			assert.equal(requests[0].options.url, '/subscriptions');
			assert.match(requests[0].options.data, /form_email=user%40example.com/);
			assert.equal(form.querySelector('.mpam-submit').disabled, true);
			requests[0].deferred.reject({}, 'timeout');
			assert.equal(form.querySelector('.error').textContent, 'Try again');
			assert.equal(form.querySelector('.mpam-submit').disabled, false);
			assert.equal(form.querySelector('.mp-am-preloader'), null);
			submit();
			assert.equal(requests.length, 2);
			requests[1].deferred.resolve({ success: false, error: '<em>Configured error</em>' });
			assert.equal(form.querySelectorAll('.error').length, 1);
			assert.equal(form.querySelector('.error em').textContent, 'Configured error');
			submit();
			requests[2].deferred.resolve({ success: true, success_message: '<strong>Welcome</strong>' });
			assert.equal(form.querySelector('.success strong').textContent, 'Welcome');
			assert.equal(form.querySelector('input'), null);
		} finally { dom.window.close(); }
	});

	test(`${asset}: invalid fields and malformed JSON recover safely`, () => {
		const { dom, form, requests, submit } = browser(asset);
		try {
			const email = form.querySelector('input[type="email"]');
			email.value = '';
			submit();
			assert.equal(requests.length, 0);
			email.value = 'valid@example.com';
			submit();
			requests[0].deferred.reject({}, 'parsererror');
			assert.equal(form.querySelector('.error').textContent, 'Try again');
			assert.equal(form.querySelector('.mpam-submit').disabled, false);
			submit();
			requests[1].deferred.resolve(null);
			assert.equal(form.querySelector('.error').textContent, 'Try again');
		} finally { dom.window.close(); }
	});
}

test('Editor popup encodes shortcode delimiters and returns false', () => {
	const dom = new JSDOM('<!doctype html>', { runScripts: 'outside-only' });
	try {
		dom.window.eval(fs.readFileSync('assets/js/shortcode-popup.js', 'utf8'));
		assert.equal(dom.window.an_mc_shortcode_attribute('Say "Hi" [x] <b>A & B</b>'),
			'Say &quot;Hi&quot; &#91;x&#93; &lt;b&gt;A &amp; B&lt;/b&gt;');
	} finally { dom.window.close(); }
});

test('Editor insertion keeps quote/bracket entities as text through the HTML parser', () => {
	const dom = new JSDOM(`<!doctype html><div id="mpmc-box-wrapper"><select id="mpam_list_ids" multiple><option selected value="audience">Audience</option></select></div>
		<input id="signup_text" value="Say &quot;Hi&quot; [x]">
		<input id="email_text" value="Email"><input id="first_name_text" value="First"><input id="last_name_text" value="Last">
		<input id="success_message" value="Thanks"><input id="failure_message" value="Retry">
		<input id="showplaceholder" type="checkbox" checked><input id="collect_first" type="checkbox"><input id="collect_last" type="checkbox">`, { runScripts: 'outside-only' });
	try {
		dom.window.jQuery = jquery(dom.window);
		let inserted;
		dom.window.tinyMCE = { activeEditor: {}, execCommand(command, ui, content) { inserted = content; } };
		dom.window.tinyMCEPopup = { editor: { execCommand() {} }, close() {} };
		dom.window.eval(fs.readFileSync('assets/js/shortcode-popup.js', 'utf8'));
		assert.equal(dom.window.an_mc_submit(), false);
		const editor = dom.window.document.createElement('div');
		editor.innerHTML = inserted;
		assert.match(editor.textContent, /button="Say &quot;Hi&quot; &#91;x&#93;"/);
		assert.match(editor.innerHTML, /&amp;quot;/);
	} finally { dom.window.close(); }
});
