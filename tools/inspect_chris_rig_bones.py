import bpy
arm=bpy.data.objects.get('Chris_Rig')
print('ARM',arm.name if arm else None)
if arm:
  for b in arm.pose.bones: print(b.name, 'parent=',b.parent.name if b.parent else '')
for n in ['ChrisStudy_Desk_Root','ChrisStudy_Chair_Root','ChrisStudy_Chair']:
 o=bpy.data.objects.get(n)
 if o: print('OBJ',o.name,tuple(round(v,3) for v in o.location),tuple(round(v,3) for v in o.rotation_euler),tuple(round(v,3) for v in o.dimensions))
