# ShopEase Lab: Student Guide

ShopEase is an owner-authorized PHP training shop. Use only the lab instance provided by your
instructor. Do not test other students' accounts, real services, or real customer data.

## Start a safe lab

1. Sign in through the lab access page, then sign in to a seeded ShopEase demo account.
2. Open **Safe Labs**. Each of the 69 configured vulnerability switches has a contained
   simulation, whether the switch is on or off.
3. Run a simulation and review its synthetic result and remediation guidance. Simulations use
   fixed in-memory examples; they do not attack the shop, change its data, or access files,
   commands, or network services.
4. After a simulation passes, enter your own name and email to claim its certificate. The
   certificate uses the same one-page landscape layout as the ShopEase Lab certificate sample:
   recipient name and email, shared demo account, achievement, certificate number, and award
   date. Safe Lab certificates say **“For completing”** and certify simulation completion only;
   they do not prove an exploit occurred or that the application is vulnerable.
5. Your certificate can be downloaded from the confirmation page and your profile while signed
   in to the demo account that owns it. Reclaiming the same lab for the same email reuses the
   existing certificate.

The Safe Labs simulation certificates are separate from the discovered-flag awards. If your
instructor gives you a challenge flag, use **Submit Flag** to claim that challenge's award.
Flag claims are self-reported (except the login SQL-injection detection flow) and are not
independent proof that a vulnerability was exploited.

## Current limitations

The switch list is not a list of 69 working vulnerabilities in the shop. Most Safe Labs entries
are simulations only; the source-reviewed inventory identifies which real PHP behaviors are
implemented, incomplete, or not demonstrated. See [VULNERABILITIES.md](VULNERABILITIES.md)
and the [Vulnerability Reference](docs/VULNERABILITY-REFERENCE.pdf).

Public registration is disabled. Use the demo accounts supplied by the instructor. Do not reset
the database or change shared accounts.
