This is a prototype custom module created to accompany a [blog post about Drupal
8's Cache API](https://chromatichq.com/blog/taco-friendly-guide-cache-metadata-drupal-8). It grabs Chromatic's HeyTaco Leaderboard using the HeyTaco API. It is not a fully functioning module, out-of-the-box as many shortcuts were taken to get the functionality working for the purposes of the blog post.

HeyTaco user names are your team's Slack user names and they are likely different
from your team's Drupal user names. Therefore, the code for this custom module requires manual intervention to get your team's HeyTaco Leaderboard to display. I manually/artificially matched up our
team's Slack and Drupal user names in `HeyTacoBlock::returnLeaderboard()`, in particular, the `$peeps` and `$partners` variables currently need manual intervention.

To make a fully-functioning contrib module, we would need, for instance, to add `field_slack_uname`
for our Drupal users and this could be used to match-up Drupal uids with
the Slack user names returned from the HeyTaco API. Also, user roles could be added to distinguish between partners and regular employees.

As a reminder, all of this was built to play with D8's caching capabilities and different users have their HeyTaco Leaderboard cached separately from other users.

Things to check for:
- Log in as one of the partners. (You do not see asterisks nor the taco padding message.)
- Log in as a non-partner. (You do see asterisks denoting that partner totals are padded.)
- Using your browser's dev tools, compare page load time before and after caching.

Inside the code and Twig template are chunks meant to display the user's picture next to their names. This functionality was not used for the blog post since I didn't want to do the styling to make it look nice. However, it should be easy enough to get it working by uncommenting out the commented line that I left in the Twig template.
