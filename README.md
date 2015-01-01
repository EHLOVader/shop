# Shop Plugin
This plugin is not completed yet, but feel free to install it and take a look around! If you'd like to see the shop components in action, take a look at the [example theme](https://github.com/scottbedard/shop-theme). Expect this repo to be changing frequently while I hammer away at the [to do list](https://github.com/scottbedard/shop/blob/master/TODO.md).


#### Products
Documentation coming soon.


#### Inventories
Products may contain multiple inventories, to manage them click the link in the "Manage Inventories" column of the Products list. Each inventory has the following properties.

| Property          | Required  | Description   |
| :---------------- | :-------: | :------------ |
| Name              | Only when multiple inventories are present | The identifying name for the inventory (small, medium, large, etc...). |
| Quantity          | Yes       | The number of items in stock. |
| Price Modifier    | No        | Modifies the price of the parent product. Example, if a 2XL shirt costs $1 extra, the price modifier would be 1. |
| Status            | Yes       | Determines if the inventory is visible / hidden. |

Inventories may be re-ordered by clicking the "move" handle, and dragging the inventory to a new position. To automatically hide out of stock inventories, set the "Out of Stock Inventories" field to "Hidden" in the Settings area.

To do list...

- Allow for "groups" of inventories belonging to a single product (For example, a product may come in many sizes, but also many colors...)


#### Categories
There are two *pseudo categories* that come with this plugin called "All" and "Sale". They behave like normal categories, but you may not add products directly to them, or delete them. All other category actions such as re-naming them or arranging their products are still available.

Products may be arranged to display their products in whatever order you would like. There are a few built in arrangements such as "newest first" or "alphabetized", but you may also apply a custom arrangement. To do this, simply drag and drop their product's thumbnail images and arrange them however you wish.

Categories that are hidden are still accessible from components. The only difference with hidden categories is that they will not show up in the list of categories. To automatically hide empty categories, set the "Empty Categories" field to "Hidden" in the Settings area.


#### Discounts
Discounts may be applied to products or categories, and use the following properties.

| Property              | Required  | Description                              |
| :-------------------- |:---------:| :----------------------------------------|
| Name                  | Yes       | A name to identify your discount by.
| Start Date            | No        | The scheduled start date for your discount. This may be left blank to start the discount immediately. |
| End Date              | No        | The scheduled end date for your discount. This may be left blank to create a never-ending discount. |
| Amount                | Yes       | The amount your discount should subtract from product prices *(see Discount Method)*. |
| Discount Method       | Yes       | This determines whether the *Amount* value represents an exact amount, or a percentage. |
| Categories / Products | Yes       | Atleast one category or product must be selected for the discount to apply to. |

Since products may belong to multiple categories, there might be situations in which multiple *category discounts* could be applied to a single product. In this situation, the discount that provides the best value for the product will be the one applied.

If a product has a *product discount*, and also belongs to categories with a *category discount*, the *product discount* will take priority and be the one applied.


#### Promotions
Documentation coming soon.