=== Poll Verdict ===
A lightweight WordPress plugin for "verdict" style Yes/No (or multi-option)
polls with a countdown timer, live results, an automatic carousel for
multiple polls, shortcodes, and a native Elementor widget.

== Installation ==
1. Zip contents already match a plugin folder: "poll-verdict".
2. In WP Admin go to Plugins > Add New > Upload Plugin, choose the zip,
   click Install Now, then Activate.
   (Or unzip into /wp-content/plugins/ via FTP/SFTP and activate from Plugins.)
3. A new "Polls" menu appears in the WP Admin sidebar.

== Creating a poll ==
1. Polls > Add New.
2. Title = the poll question (e.g. "Should this popular TV show take a leap?").
3. Main editor = the description text under the title.
4. Featured Image (optional) = replaces the default gold scale icon.
5. "Poll Settings" box:
   - Badge Label (default: CURRENT VERDICT)
   - Vote Button Text (default: VOTE NOW)
   - Voting Ends On — pick a date/time for the countdown, or leave blank
     for a poll with no countdown.
   - Show Results Sidebar — toggle the countdown + live-results panel.
   - Reset Votes — tick and Save/Update to zero out vote counts.
   - Poll Image — upload/select an image from the Media Library right in
     this box. This is what shows above the Yes/No buttons for this poll.
     If left empty, the plugin falls back to the post's Featured Image,
     then to the site-wide fallback image below, then to a default gold
     scale icon so something always displays.
6. "Poll Options / Choices" box:
   - Defaults to Yes / No, which automatically renders with the
     thumbs-up / thumbs-down verdict styling from the reference design.
   - Add as many options as you like for a standard multiple-choice poll
     (renders as a stacked list with a progress bar per option instead).
7. Publish.

== Settings ==
Polls > Settings lets you upload one site-wide "Fallback Poll Image".
It's used automatically for any poll that has no Poll Image and no
Featured Image set — handy so new polls never show the bare default
icon while you're still adding artwork.

Image priority for the graphic shown above each poll's options:
1. That poll's own "Poll Image" (set on the poll's edit screen)
2. That poll's Featured Image
3. The site-wide fallback image (Polls > Settings)
4. A built-in default scale icon (always available, needs no setup)

== Shortcodes ==
- [poll_verdict id="123"]
  Shows one specific poll.

- [poll_verdict]
  Smart shortcode: shows your latest polls; if only one exists it
  displays as a single card, if more than one exists it automatically
  becomes a carousel. Optional attributes: limit="5" order="date" sort="DESC".

- [poll_verdict ids="4,7,9"]
  Carousel of specific polls, in that order.

- [poll_verdict_carousel ids="4,7,9" show_arrows="yes" show_dots="yes" autoplay="no" interval="6000"]
  Forces carousel mode (even for a single poll) with full control over
  arrows, dots and autoplay.

Every poll's edit screen has a "Shortcode" box in the sidebar with the
exact shortcode pre-filled for copy/paste.

== Elementor ==
Drag the "Poll Verdict" widget (Poll Verdict category) into any section.
Controls let you pick Auto / Single / Carousel display mode, choose
specific polls, and configure arrows/dots/autoplay — no shortcode typing
required. Works in the Elementor editor preview as well as the live page.

== Notes ==
- One vote per browser is enforced with a cookie (no login required).
- Votes are stored as post meta on each poll; suitable for typical
  traffic. For very high-concurrency voting you may want to move vote
  counting to a dedicated database table.
- All styling lives in assets/css/poll-verdict.css — colors are defined
  as CSS variables at the top of the file (--pv-navy, --pv-blue,
  --pv-green, --pv-red, --pv-gold, etc.) so you can re-theme the widget
  by editing a handful of values.
