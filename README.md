# Brendan's Notes

### Steps to get local working
1. access server via http://localhost:8080/public
2. generate encryption key with `artisan key:generate`
3. create empty sqlite file at `database/database.sqlite`
4. Add `/database/migrations` directory with migration files from basic Laravel install
5. run migrations with `artisan migrate`

### Assumptions/Decisions
- I'm assuming that the generated report (from dynamic data in Stripe test account) and the creation of the new customer and subscription are independent implementations that could be triggered by separate commands. Obviously crating a new subscription should affect the output of the report since it will modify the data in the Stripe account.
- I'm assuming that creating the customer, their subscription, and the mid-cycle proration (all through the API) should be handled as separate services in the backend. For the sake of this assignment I'll create a single command to execute these changes for testing the report.
- I'm assuming that the mid-cycle upgrade will have proration applied immediately (vs at the end of the month) so the added invoiced amount will be added to the 5th month in the report.
- It would be easiest to create the new customer, subscription, and upgrade (using stripe schedule) in the fixtures with the other data since then we'd have access to dynamically generated stripe ids like `${price_monthly_crossclip_basic:id}`. My assumption is that I'm required to create these through the API and it looks like the upgrade should occur when the data is advanced to the 5th month using the Test Clock rather than setting it up ahead of time using a subscription schedule with phases. As such I either need to pass the dynamic stripe ids through .env/config/command params or query the API for them.
- In order to avoid setting up webhooks to detect when the time clock is advanced (which seemed outside the scope of this assignment) I will simply poll the API repeatedly after advancing the test clock until I know that it's "ready" again. That way I can advance the clock all the way through the simulation and generate the report in a single command and avoid having to keep track of any state locally (such as in the db) and only rely on the Stripe API for data.
- NOTE: Depending on when the simulation starts you could have slightly different results in the table due to differences in the length of each month. For example the trial for the new customer is supposed to last for 30 days (instead of 1 month) so starting on January 1 would result in the first invoice occurring in January versus starting onn February 1st would result in the first invoice occurring in March.

### Questions
- How to handle projection of expected revenue for a subscription. Assuming that it's supposed to know about things like the 5th month upgrade, trials, and other time based events. Can this be done without relying on stripe test clock manipulation? or am I supposed to rely on test clock as the mechanism to "project" the earnings for each month.

### Workplan
- [x] Using the Stripe API, create a new customer with subscription
  - [x] Create customer in Stripe via API service
  - [x] Create subscription with schedule for customer via API service
- [x] Advance Test Clock through year-long simulation
  - [x] Create function to advance clock and poll api until clock is in 'ready' state
  - [x] Loop on increasing start time and advance clock until it's past a year from start date
- [ ] Return a table for each product that lists out a subscription per row.
  - [x] The columns should be the following: customer email, product name, ...months 1-12, lifetime value for subscription. The final row should contain usd totals for each month.
  - [ ] Retrieve product/subscription/invoice data via Stripe API
  - [ ] Transform product data from Stripe objects to report DTO via a new service
  - [ ] Pull transformed data into command and use to build table (including row for totals)
- [ ] Add screenshot to readme
- [ ] Add instructions to run code and tests to readme

### Nice-to-haves/TODOs
- Use an interface/repository/factory pattern to abstract getting data from Stripe API specifically (although this analysis is heavily dependant on stripe-specific features i.e. test clock)
- Pull in external library like `moneyphp/money` to handle currencies and conversions
