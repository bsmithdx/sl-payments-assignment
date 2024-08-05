# Brendan's Stripe Subscription Analysis Assignment
![Assignment Screenshot](/screenshot.png?raw=true "Assignment Screenshot")
### Instructions
- Follow steps from READMEOLD to run local Docker container
  - `make up`
  - `make composer-install`
  - `make stripe-login`
- Copy `.env.example` to `.env` and add stripe secret key for whatever account you will be using to `.env` as `STRIPE_API_KEY=`
- You can run PHPUnit tests with `make artisan CMD=test`
- Run `make stripe-fixture` to seed data to test Stripe account (Note: this will create the Test Clock as well)
- Run the Analysis via the command line with `make artisan CMD=app:generate-subscription-analysis`
- NOTE: Make sure to delete the test clock and all test data via the Stripe Dashboard before running the command again.


### Assumptions/Decisions
- I'm assuming that the mid-cycle upgrade will have proration applied immediately (vs at the end of the month) so the added invoiced amount will be added to the 5th month in the report.
- In order to avoid setting up webhooks to detect when the time clock is advanced (which seemed outside the scope of this assignment) I will simply poll the API repeatedly after advancing the test clock until I know that it's "ready" again. That way I can advance the clock all the way through the simulation and generate the report in a single command and avoid having to keep track of any state locally (such as in the db) and only rely on the Stripe API for data.
- I usually don't commit "TODO" comments, but I left them here to show where I might spend additional time making improvements given more time
- NOTE: Depending on when the simulation starts you could have slightly different results in the table due to differences in the length of each month. For example the trial for the new customer is supposed to last for 30 days (instead of 1 month) so starting on January 1 would result in the second invoice occurring in January versus starting on February 1st would result in the first invoice occurring in March.

### Workplan
- [x] Using the Stripe API, create a new customer with subscription
  - [x] Create customer in Stripe via API service
  - [x] Create subscription with schedule for customer via API service
- [x] Advance Test Clock through year-long simulation
  - [x] Create function to advance clock and poll api until clock is in 'ready' state
  - [x] Loop on increasing start time and advance clock until it's *almost* a year from start date
- [x] Return a table for each product that lists out a subscription per row.
  - [x] The columns should be the following: customer email, product name, ...months 1-12, lifetime value for subscription. The final row should contain usd totals for each month.
  - [x] Retrieve product/subscription/invoice data via Stripe API
  - [x] Transform product data from Stripe objects to report DTO via a new service
  - [x] Pull transformed data into command and use to build tables (including rows for totals)
- [x] Handle currency conversion and formatting 
- [x] Add screenshot to readme
- [x] Add instructions to run code and tests to readme
