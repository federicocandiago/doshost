<?php
/** Cookie Policy — Art. 5(3) Directive 2002/58/EC (ePrivacy), Art. 122 of the Italian Privacy Code, Garante guidelines of 10 June 2021. */
$email = legal('email');
$mail = '<a href="mailto:' . e($email) . '">' . e($email) . '</a>';
?>
<article class="legal mx-auto">
    <h1 class="h3 mb-2"><?= e(__('Cookie Policy')) ?></h1>
    <p class="text-secondary small mb-5"><?= e(__('Last updated: :date', ['date' => legal('date')])) ?></p>

    <p><?= e(__(':app uses only the cookies and browser storage strictly necessary to work. We do not use analytics, advertising, profiling or social media cookies, and no third party places cookies through our site. For this reason no consent banner is shown.', ['app' => env('APP_NAME')])) ?></p>

    <h2>1. <?= e(__('What cookies are')) ?></h2>
    <p><?= e(__('Cookies are small text files that a website stores in your browser and reads back on later visits. Similar technologies, such as the browser\'s local storage, work in the same way and are covered by this policy too.')) ?></p>

    <h2>2. <?= e(__('Cookies we use')) ?></h2>
    <div class="table-responsive surface mb-3">
        <table class="table small mb-0">
            <thead><tr>
                <th><?= e(__('Name')) ?></th><th><?= e(__('Type')) ?></th><th><?= e(__('Purpose')) ?></th><th><?= e(__('Duration')) ?></th>
            </tr></thead>
            <tbody>
                <tr>
                    <td class="mono"><?= e(env('SESSION_NAME', 'doshost_session')) ?></td>
                    <td><?= e(__('Technical, first-party')) ?></td>
                    <td><?= e(__('Keeps you signed in, protects forms against cross-site request forgery, remembers the language you chose and shows confirmation messages.')) ?></td>
                    <td><?= e(__('Until the browser is closed')) ?></td>
                </tr>
            </tbody>
        </table>
    </div>
    <p><?= e(__('The cookie is marked HttpOnly (not readable by scripts) and SameSite=Lax (not sent on requests started by other sites), and it is sent only over HTTPS when the site uses it. It does not contain personal data: only a random identifier of your session.')) ?></p>

    <h2>3. <?= e(__('Other data stored in your browser')) ?></h2>
    <ul>
        <li><strong><?= e(__('Emulator')) ?>:</strong> <?= e(__('js-dos, the DOS emulator, may keep its display preferences in local storage and temporary copies of your instance in the browser\'s private storage (OPFS). The temporary copies are cleared every time an instance starts; your disk is always saved on our server.')) ?></li>
        <li><strong><?= e(__('Interface')) ?>:</strong> <?= e(__('some screens remember small interface choices for the current tab only (session storage), for example which panels are open.')) ?></li>
    </ul>
    <p><?= e(__('These data never leave your device and are not used to track you.')) ?></p>

    <h2>4. <?= e(__('Legal basis')) ?></h2>
    <p><?= e(__('Strictly necessary cookies and storage do not require consent under Article 5(3) of Directive 2002/58/EC, Article 122 of the Italian Personal Data Protection Code and the Garante\'s guidelines on cookies of 10 June 2021. The related processing is based on the performance of the contract (Art. 6(1)(b) GDPR).')) ?></p>

    <h2>5. <?= e(__('How to manage cookies')) ?></h2>
    <p><?= e(__('You can delete or block cookies from your browser settings at any time (usually under "Privacy" or "Site data"). Blocking the session cookie prevents you from signing in; deleting it signs you out.')) ?></p>

    <h2>6. <?= e(__('Contacts and updates')) ?></h2>
    <p><?= __('For any question write to :mail. More information on how we process personal data is available in the <a href=":privacy">Privacy Policy</a>. If we ever introduce cookies that need consent, we will update this policy and ask for your consent first.', ['mail' => $mail, 'privacy' => url('/privacy')]) ?></p>
</article>
