import bpy
root=bpy.data.objects.get('ChrisStudy_Desk_Root')
for o in [root]+list(root.children_recursive): print(o.name,o.type,tuple(round(v,3) for v in o.location),tuple(round(v,3) for v in o.rotation_euler),tuple(round(v,3) for v in o.dimensions))
