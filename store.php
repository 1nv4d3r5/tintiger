<?php
/* store.php - all the code needed to sell stuff:

Glossary:
    Category
    Item:
    Attribute:
    GarbageCollection: a timed script that cleans up specific elemts left hanging. THese include manual-process alerts, deadtime carts, etc.
    gateway: vendor package/API used to provide outside service like PayPal or VISA pay methods or USPS or UPS shipping

Screens:
    catalog:
        catagories screen: 
            catagories of items in LeftMenu. RightSide displays items in category. 
                if no Category, display Specials in RightSide.
            Clicking an item goes to SingleItem screen w/ more info
        SingleItem screen:
                page(s) of items (pic, desc, Attributes, price, quan box, 'add to cart')
                price can change according to Attributes (Yik - javascript)
                can display multiple Attributes as in different sizes, colors, etc (shirts?)
                An item is the bottom level of a Category. Further definition is via Attributes
                        drop downs for Attributes - price, quan, etc for each.
                WestMarine displays  a table of all Attributes below main desc w/ 'add to cart' though 
                        item also has drop down boxen for the Attributes. 2 ways to do 1 thing. 
                navigation:  search, LeftMenu, checkout,  back to category screen
                    search - 
                    LeftMenu links are Catagories - category screen (indent each sub-Category)
                    checkout - same as 'view cart', prepare to finalize selected items
                'Customers also bought' at bottom of page?
      search results:
            display a list of parts of entries - google like
                manuf list links goto category(ies) or items if <n items
                title links goto SingleItem screen
                desc links display so many words either side of keyword(s) for so many bytes. links goto items or Cat as desc_type
                
            this could be difficult. search on what? keywords in desc, title, manuf, 
            build indices via cron scripts
     login/logout
        username, password, email psw, login
        <logout should ask to delete cart or move to wishlist>
        link to login|logout should be displayed on all screens 
        How long is a cart to be held until deleted or converted to wishlist if user goes away? GarbageCollection
            Customer settable for default action
    checkout | view cart:
        see items, review quans, with Attributes (cart Atribs and Cats combined to make one element.)
        select shipping - shipping gateways?
        <coupons processing> keep this in mind since processing will take more code...
        select payment methods
        navigation: update, confirm, continue shopping
            update - quans, shipping, payment method
            confirm - if no changes, payment gateway, complete order, else 'update'
            continue - go back to prev item page
        how long to keep abandoned carts, logged in vs guest if different
    customer:
        create/update account - name, psw, profile (avatar, public/private)
        addresses - billing and shipping, phone, email, other (facebook, etc)
        payment methods - associate with addresses, verification
        wishlists - review, modify, convert to cart
    Point-of-Sale:
        ? do we even want to do this?
    admin:
        mess with orders
            shipping process - packing, mailing labels, order details, tracking #s, confirmations
            returns and refunds
            back orders, pre orders?
            restocking from vendors - what to do when items recieved from vendors
            customer services - manual processes
        mess w/ Specials - promo codes/coupons, grouping items, 
            what to do with closeouts,
        shipping gateways - install, delete, activate, test
        payment gateways - install, delete, activate, test
        mess with vendors
            vendor name, address, payment, 
            orders? 
            merge arrived items into existing inventory
        customer admin - 
            display list of customers/search for via column value
            on select 1 customer, display complete profile for customer (view CC#, psw?)
                what 
        catalog admin - mess w/ items, catagories, attributes
        Reports: Sales Summary|Detail, Sales by Category|Warehouse|Shipper|etc, Taxation breakdowns, 
            Report Preferences | Generater?
            Archived Reports
Tables:
a table of items
    contains attributes of each item in stock
        base description, price, quan, Attributes, wharehousing, stock quan, reorder quan, 
     contains link back to Category(ies)
    how to maintain Attributes - use drop down lists (maintainable...) to change 
    maintain location and quan of items. an Item can exist in multiple places.
    Categories:
        key is delimited string of categories. 
        also sets default attributes w/ or w/o values
            ex. Top:Clothing:Mens:Shirts:Cotton Polo | style, size, color, material| H, W, D, #, ship method, shipFrom, skuPrefix, 
            ? where does the shift from Cat to Attrib occur? Does it have to? - can everything be an Attrib? yes
a table of customers:
    login, identification (name, address, )
    link to orders, wishlists - named wishlist screen...
a table of orders/wishlists    
    item_id, quan, sub_cats (size, color, etc), price_per
    customer_id, item_id required - joins one or more items to a customer
    diff twixt order and wishlist: no prices, name, status
    if order turned into wishlist, remove price_per, move to wishlist table for customer_id. Named lists?
        only 1 order but multiple wishlists allowed, link to a customer
     customer publish, share, pass-on a wishlist? email it to someone?
a table for vendors:
    name & address info for each, contact, payment methods
    a table for POs from vendor - what we bought from them and where are they
    ? online catalogs parsed into table?
the shopping cart -
    a method to store choices - $_SESSION and database
payment gateways:
    PayPal, WorldPay, credit cards, MOs, COD, cash (POS system, too) 
    Admin screen to accept/validate/authenticate MOs, cash, etc
        payment declination processing?
shipping gateways:
    order aggregation - items for an order from multiple locations
    
process:
    - user goes to Catalog
    - user can see Specials on category selection screen (cat in LeftMenu, Specials in RightSide)
        Specials appear as normal items
    - user selects category, display 1st items in Category
    - user selects item(s) and/or navigates
    - user navigates to checkout
        if !loggedIn, cause account creation and/or login
        review cart
        select shipping method, address
        <select coupons>
        select payment method
        confirm items, shipping, payment or move to wishlist
            perform payment
        finish - send email invoice, pull-list, etc
        
        Admin
        - on Login of Admin display admin choices
        - on selection of, perform an activity... obvious, ain't it?
        
When Category Ends, what do? How are Attributes defined for an Item?
- grab attributes by category, overlay existing attributes with latest deeper category attributes.
category            attribute   value   key             price       reorder     
mens                                                                                          10
mens:pants                                                                                  5
mens:shirt                                                          10.00              12
mens:shirt:style    polo
mens:shirt           size            XS      XS               
mens:shirt           size            S           S       
mens:shirt           size            M           M
mens:shirt           size            XXL        XXL     15.00                 
mens:shirt           color           grey
mens:shirt           color       

- items are made up of attributes. attributes are categories until only 1 record is found 
        

notes:misc:ideas:blah:
- shipping from multiple locations
- multiple item storage locations, distributed inventory
- wishlist - ordered items sans prices
*/

?>
