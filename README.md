# Shop Plugin
This plugin is not completed yet. Feel free to install it and play around.


### Products
---
Documentation coming soon.


### Inventories
---
Documentation coming soon.


### Categories
---
Documentation coming soon.


### Discounts
---
Discounts may be applied to either products or categories, and have the following parameters

| Parameter             | Required  | Description                                       |
| :-------------------- |:---------:| :-------------------------------------------------|
| Name                  | Yes       | A name to identify your discount by.
| Start Date            | No        | The scheduled start date for your discount. This may be left blank to start the discount immediately |
| End Date              | No        | The scheduled end date for your discount. This may be left blank to create an never-ending discount. |
| Amount                | Yes       | The amount your discount should subtract from product prices *(see Discount Method)* |
| Discount Method       | Yes       | This determines whether the *Amount* value represents an exact amount, or a percentage. |
| Categories / Products | Yes       | Atleast one category or product must be selected for the discount to apply to. |

Since products may belong to multiple categories, there might be situations in which multiple *category discounts* could be applied to a single product. In this situation, the discount that provides the best value for the product will be the one applied.

If a product has a *product discount*, and also belongs to categories with a *category discount*, the *product discount* will take priority and be the one applied.


### Promotions
---
Documentation coming soon.