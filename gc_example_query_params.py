# Shipments for order with id 138
{"filters":[
    {"name":"logistic_order", "op":"has", "val":
        {"name":"logistic_order_items", "op":"any", "val":
            {"name":"order_item", "op":"has", "val":
                {"name":"order", "op":"has", "val":
                    {"name":"id", "op":"eq", "val":"138"}
                }
            }
        }
    }]
}

# Orders with related shipments created/updated since 2014-01-01
{"filters":[
    {"name": "order_items", "op": "any", "val":
        {"name": "logistic_order_items", "op": "any", "val":
            {"name": "shipment_items", "op": "any", "val":
                {"name": "shipment", "op": "has", "val":
                    {"name": "updated", "op": "geq", "val": "2014-01-01"}
                }
            }
        }
    }]
}
