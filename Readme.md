[![Build Status](https://travis-ci.com/SamirBoulil/slub.svg?branch=master)](https://travis-ci.com/SamirBoulil/slub)
[![Maintainability](https://api.codeclimate.com/v1/badges/afb6042b14df680869f2/maintainability)](https://codeclimate.com/github/SamirBoulil/slub/maintainability)
[![Test Coverage](https://api.codeclimate.com/v1/badges/afb6042b14df680869f2/test_coverage)](https://codeclimate.com/github/SamirBoulil/slub/test_coverage)
[![FOSSA Status](https://app.fossa.com/api/projects/git%2Bgithub.com%2FSamirBoulil%2Fslub.svg?type=shield)](https://app.fossa.com/projects/git%2Bgithub.com%2FSamirBoulil%2Fslub?ref=badge_shield)

# tldr;
- If you found a bug, open an issue ticket [here](https://github.com/SamirBoulil/slub/issues/new).
- Put your PR to review in Slack py posting a slack message having one of the following structure:

        Slub user: ... TR ... {PR link} ...
        Slub user: ... review ... {PR link} ...
        Slub user: ... PR ... {PR link} ...

- Unpublish a PR from the reminders by mentioning @Yeee:

        @Yeee unpublish {Link of the Github PR}


# Welcome to Slub
Slub improves the feedback loop of your pull requests (PRs) within your team.

The name Slub comes from the contraction of Slack & Github: **Sl** (ack+gith) **ub**.

***Yeee** is the name of the bot in your slack team.*

[![Yeee](https://img.youtube.com/vi/2Jvvz8n_hZ0/0.jpg)](https://www.youtube.com/watch?v=2Jvvz8n_hZ0 "Yeeee")

# Found a bug ?
You can [open a new issue](https://github.com/SamirBoulil/slub/issues/new) in the issue's repository section.

# The project
This project has been designed and first put in production in march 2019. It is currently maintained by [Samir Boulil](https://github.com/samirboulil).

## Release notes
Each new version of Slub comes with a new set of features and bug fixes.

Find the published release notes below:
- April 2020, [Releasing Yeee 1.2](https://medium.com/@samir.boulil/releasing-yeee-1-2-36573723b92e)
- November 2019, [Releasing Yeee 1.1](https://medium.com/@samir.boulil/releasing-slub-1-0-63c58756f923)
- March 2019, [Releasing Yeee 1.0](https://medium.com/@samir.boulil/releasing-slub-1-0-63c58756f923)

## Features
Slub improves the feedback loop of your PRs in multiple ways:
- it notifies the author of a pull request when a PR is reviewed by a teammate.
- ot notifies the author of a PR when the continuous integration (CI) status changes.
- it keeps the team up to date regarding the number of approvals a PR has, it's CI status, and the PR status.
- it sends daily reminders to your team regarding the PRs missing reviews.

## How to use
Slub listens to any PR sent to review in a slack channel which slub is configured to listen to.

Once Slub hears a PR to review, it will start keeping track of some information like:
- The number of comments (GTMS, Refused and simple comments)
- The CI status (Green, Red, or Pending)
- The PR status (open, closed, or merged)

Any message having the following structure can be tracked by @Yeee

    Slub user: ... TR ... {PR link} ...
    Slub user: ... review ... {PR link} ...
    Slub user: ... PR ... {PR link} ...

## Notifications
Whenever a new change happens to a PR, Slub will notify the author and/or the squad of the change using Slack.

### Notifying the author
![Notify author](https://miro.medium.com/max/872/1*Sd9GpS2aXwo9KYYfkeAVAQ.png)

Author notifications such as “Your PR has been commented” will be sent to Slack in the thread where the PR link was sent (hence, only the author is notified that a new message was sent in a thread).
- The author is notified whenever a review is added to the PR
- The CI status changes

### Notifying the squad
The squad is not notified per say, but it can follow the PR statuses by looking at the emojis added to the PR link.

![Notify squad](https://miro.medium.com/max/1350/1*wYmeULsOHzn_oX9UZLp9Xg.png)

The squad can directly see:
- The number of GTMS the PR currently has

![Number of GTMs](https://miro.medium.com/max/146/1*UE2fYlXN13cgHM5jqng1wg.png)

- The CI status (pending, red or green)

![CI statuses](https://miro.medium.com/max/202/1*3-zBCweArdz6YZ6T0cHhOg.png)

- When the PR is merged

![PR merged](https://miro.medium.com/max/82/1*Wpc21XHITPgQMedSmfntgw.png)

The team can then quickly see at a glance the PRs that needs review from those being already merged.

## Reminders

A reminder is a synthesized list of all the PRs that are to review in a channel and that do not have 2 GTMs yet.

### Remind me!

It is published once a day around 9:30 AM (GMT+1) in this form:

![Reminders](https://miro.medium.com/max/1262/1*o7zWCsXAQpSiy4a5n6jWeg.png)

When available you get the following information for one PR to review:
- The author of the PR
- The title of the PR
- How many days it has been put to review
- The PR link

### Unpublishing

If you have put a PR to review, had some comments back and need to rework on it. You might want to remove this PR from the daily reminder. That’s what “unpublishing a PR” is all about.

The way you unpublish a PR from the reminder is by telling Slub in the following way:

        @Yeee unpublish {Link of the Github PR}

*In this example, the slub bot name is @Yeee*

![Unpublishing reminders](https://miro.medium.com/max/1760/1*lKYEB13fVb1lQzvL5pVtKg.png)
