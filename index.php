<?php
// Defines the letter.
$letter = 'To whom it may concern,

I am a $gender and after reading the recent technical consultation on ‘Toilets for men and women’ I am utterly compelled to contact you. 

This consultation is a direct and violent attack on transgender and Gender Non Conforming (GNC) people’s basic human rights. 

There is no evidence that cisgender people face increased violence in gender neutral toilet facilities. However, we do have evidence that almost half of trans people (48%) don’t feel comfortable using public toilets, as a result of verbal abuse, intimidation, and physical assault (LGBT in Britain Trans Report, Stonewall UK, 2018). The policing of gender in toilets is a wasteful use of government funds, serving to draw unwarranted attention to a political and prejudicial ‘debate’ resulting from a wider climate of transphobia in the UK.

As stated in the consultation: ‘The Equality Act provides that sex, age, disability and gender reassignment are protected characteristics.’ I would like to highlight that the Equality Act of 2010 also serves to protect those who are discriminated because they are wrongly perceived to be trans (including many Black women, butch women and lesbians, GNC people and intersex people), many of whom face abuse and discrimation due to a combination of racism and gender policing, and therefore rely on gender neutral toilets as a safer alternative. Whilst this is not yet in the Equality Act, GNC and nonbinary people (including disabled nonbinary people) should also be entitled to gender neutral toilets, or to their personal preference of gendered facility. 

The consultation also states that “Women need safe spaces given their particular health and sanitary needs (for example, women who are menstruating, pregnant or at menopause)”. This statement completely excludes the experience of trans men, intersex people and GNC people who menstruate / are pregnant / at menopause. The government’s continued erasure of already marginalised groups of people serves to reiterate the inequality in distribution of public resources privileging cisgender people. 

As a $gender I have never, ever felt unsafe or at risk from being in gender neutral toilet spaces. $personal_experiences

The consultation states that you want to ensure that everyone is fairly served. I urge you to take seriously the negative effects that the removal of gender neutral toilets will have on the following groups - Black women, lesbian / butch women, trans and nonbinary people, GNC people, and disabled trans people - all of whom experience adverse levels of violence due to the effects of gender policing, and the compounded effect of racism, which threatens many women of colour due to racist ideas of femininity. 
 
So I urge you not to remove gender neutral toilets. These spaces are not only safe but absolutely vital in the protection of so many people’s basic human rights. These spaces simply must not be taken away from marginalised groups of people who already face disproportionate levels of violence and abuse.

It is apparent that through this consultation the government has aligned itself with groups who intend to curb the rights of transgender people in the UK. It is dog whistle politics, focusing on the scapegoating of marginalised people rather than the issue at hand; increasing access to public toilet facilities. Gender neutral toilets are beneficial for a range of people and situations - for example, parents with children of a different gender; those who care for people of a different gender; some disabled people who have a personal assistant of a different gender, and both cisgender and transgender people who experience gender presentation scrutiny in public spaces. 

The government claims that the intention for this consultation is to provide ‘dignity and respect for all’. I demand that they truly provide this dignity and respect by listening to the voices and needs of trans people and their allies. 

Regards,
$name
';

// Load compose.
require "vendor/autoload.php";

// Load the postgres connection.
$pgConn = pg_connect(getenv("POSTGRES_CONNECTION_STRING"));

// Defines the error.
$err = null;

// Defines the sendgrid client.
$sendgrid = new SendGrid(getenv("SENDGRID_API_KEY"));

// Handle if this is a POST request.
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $senderId = null;
    try {
        // Validate the hCaptcha.
        $data = array(
            'secret' => getenv("HCAPTCHA_SECRET"),
            'response' => $_POST['h-captcha-response']
        );
        $verify = curl_init();
        curl_setopt($verify, CURLOPT_URL, "https://hcaptcha.com/siteverify");
        curl_setopt($verify, CURLOPT_POST, true);
        curl_setopt($verify, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($verify, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($verify);
        $responseData = json_decode($response);
        if (!$responseData->success) throw new Exception("hCaptcha is missing.");

        // Get the POST information.
        if (!isset($_POST["name"])) throw new Exception("Name is not set.");
        $name = trim($_POST["name"]);
        if ($name === "") throw new Exception("Name cannot be blank");
        if (!isset($_POST["gender"])) throw new Exception("Gender is not set.");
        $gender = trim($_POST["gender"]);
        if ($gender === "") throw new Exception("Gender is not set.");
        if (!isset($_POST["email"])) throw new Exception("E-mail is not set.");
        $email = trim($_POST["email"]);
        if ($email === "") throw new Exception("E-mail is not set.");
        if (!isset($_POST["additional_info"])) throw new Exception("Additional info is not set.");
        $additional_info = trim($_POST["additional_info"]);

        // Fill out the template.
        if ($additional_info !== "") $additional_info = "\n\n" . $additional_info;
        $str = strtr($letter, array(
            "\$personal_experiences" => $additional_info,
            "\$name" => $name,
            "\$gender" => $gender
        ));

        // Create email HTML with this.
        $html = nl2br(htmlspecialchars($str));

        // Create the sender ID.
        $gen = uniqid();
        if (!pg_insert($pgConn, "letters", array("id" => $gen, "email" => $email))) throw new Exception(pg_last_error($pgConn));
        $senderId = $gen;

        // Send the e-mail.
        $email = new SendGrid\Mail\Mail();
        $email->setFrom($senderId . "@sg.jakegealer.me", "Down With Transphobia");
        $email->setSubject("Letter by " . $name . " generated by Down With Transphobia");
        $email->addTo(" toilets@communities.gov.uk", "Toilets Review");
        $email->addContent("text/html", $html);
        $sendgrid->send($email);

        // Redirect page and exit.
        header("Location: /thank_you.html");
        exit;
    } catch (Exception $e) {
        // Put the exception into the variable.
        $err = $e;

        // Handle destroying the sender ID if it exists.
        if ($senderId !== null) pg_delete($pgConn, "letters", array("id" => $senderId), PGSQL_DML_ESCAPE);
    }
}
?>
<!DOCTYPE HTML>
<html lang="en">
    <head>
        <title>Down With Transphobia</title>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="author" content="Jake Gealer">
        <meta name="description" content="A project to send a message to the UK government that transphobia isn't okay.">
        <meta property="og:type" content="website">
        <meta property="og:title" content="Down With Transphobia">
        <meta property="og:url" content="https://down-with-transphobia.jakegealer.me">
        <meta property="og:description" content="Down With Transphobia">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bulma/0.7.5/css/bulma.min.css">
    </head>

    <body>
        <div class="modal" id="preview">
            <div class="modal-background"></div>
            <div class="modal-card">
                <header class="modal-card-head">
                    <p class="modal-card-title">Letter Preview</p>
                    <button class="delete" id="preview_close" aria-label="close"></button>
                </header>
                <section class="modal-card-body" id="preview_content"></section>
            </div>
        </div>

        <br />
        <div class="container">
            <?php
            if ($err !== null) {
                echo '<div class="notification is-danger">' . htmlspecialchars($err->getMessage()) . '</div><br />';
            }
            ?>
        </div>
        <div class="container has-text-centered">
            <h1 class="title">Down With Transphobia</h1>
            <h2 class="subtitle">A project to send a message to the UK government that transphobia isn't okay.</h2>
        </div>
        <hr />
        <div class="container">
            <h1 class="title">Why was this website created?</h1>
            <p>
                <?php
                echo nl2br('The UK government recently put out <a href="https://www.gov.uk/government/consultations/toilet-provision-for-men-and-women-call-for-evidence/toilet-provision-for-men-and-women-call-for-evidence">a proposal</a> calling for provisions on public toilets and adding that building standards guidance should have a "steer clear" of gender neutral bathrooms. This is making the government stance that gender neutral bathrooms are wrong. Considering the anti-LGBTQ+ views from this government, it is clear this is a transphobic dog-whistle, especially since the arguments raised are misinformed.

A <a href="https://docs.google.com/document/d/1cZ4Vw4rfVV2GqF4y3cToIHApxGhpoop38x8FTwkGj0c/edit">Google Doc</a> with a letter template was created by <a href="https://twitter.com/weexistlondon">We Exist London</a>. However, the idea of giving your email address to the government might be enough to steer people away from acting. Additionally, emailing might cause additional complication which someone may not be willing to put in. I am of the opinion that the more people that act on this bill, the better. This service will use your email address and the generate a random email to send the email to the government. The email address is securely stored (not the rest of the form) in a secure database ran by me which allows any replies to be forwarded to you by checking which email the reply was sent to from the database and then forwarding that reply to you. The email template is based on the one by that Twitter user so credit goes to them. <a href="https://github.com/JakeMakesStuff/down-with-transphobia">Want to contribute something to the tool? It is open source.</a>');
                ?>
            </p>
        </div>
        <hr />
        <div class="container">
            <h1 class="title">How can I help?</h1>
            <p>You can use the form below to send an e-mail to the government. If you'd rather email yourself, you can use the Google Doc template listed above.</p>
            <br />
            <form method="POST">
                <div class="field">
                    <label>
                        <span class="label">Name:</span>
                        This is sent in the letter to the government, helping to show that each of these letters are a genuine concern by a genuine person.
                    </label>
                    <input id="name" class="input" name="name" type="text" placeholder="Name">
                </div>
                <div class="field">
                    <label>
                        <span class="label">Email Address:</span>
                        Your email address is not shared with the government, but is stored in our internal database for the sole purpose of so we can forward replies from them onto you.
                    </label>
                    <input id="email" class="input" name="email" type="email" placeholder="Email Address">
                </div>
                <div class="field">
                    <label>
                        <span class="label">Gender:</span>
                        Your gender is sent in the email to show the government the diverse group of people against this.
                    </label>
                    <br />
                    <i>I am a </i><input id="gender" class="input" name="gender" type="text" placeholder="Gender">
                </div>
                <div class="field">
                    <label>
                        <span class="label">Personal Experiences:</span>
                        If you have any personal experiences you would wish to share with the government to strengthen the case, you can put them here. If not, you can keep this field blank.
                    </label>
                    <textarea id="additional_info" class="textarea" name="additional_info" placeholder="Personal Experiences"></textarea>
                </div>
                <div class="field">
                    <label>
                        <span class="label">CAPTCHA Verification:</span>
                        This proves you are not a robot.
                    </label>
                    <div class="h-captcha" data-sitekey="<?php echo getenv("HCAPTCHA_SITE_KEY"); ?>"></div>
                </div>
                <br />
                <div class="buttons" id="buttons">
                    <input class="button" type="submit" value="Submit" />
                    <a class="button" href="javascript:showPreview()">Preview</a>
                </div>
            </form>
            <br />
        </div>
        <script>window.letter = <?php echo json_encode($letter); ?>;</script>
        <script src="https://hcaptcha.com/1/api.js" async defer></script>
        <script src="/preview.js"></script>
    </body>
</html>
