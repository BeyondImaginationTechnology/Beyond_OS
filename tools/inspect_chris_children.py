import bpy
root=bpy.data.objects.get('Chris_Root')
print('ROOT', root.name if root else None)
if root:
  for o in [root]+list(root.children_recursive): print(o.name, o.type, 'parent=',o.parent.name if o.parent else None)
